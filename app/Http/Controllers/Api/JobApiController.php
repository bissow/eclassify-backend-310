<?php

namespace App\Http\Controllers\Api;

use App\Models\Item;
use App\Models\JobApplication;
use App\Services\FileService;
use App\Services\NotificationService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

/** @tags Job */
class JobApiController extends BaseApiController
{
    /** Apply Job */
    public function applyJob(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required',
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mobile' => 'required|string|max:20',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:7168',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $userId = Auth::id();
            $post = Item::approved()->notOwner()->findOrFail($request->item_id);
            $alreadyApplied = JobApplication::where('item_id', $request->item_id)
                ->where('user_id', $userId)
                ->exists();

            if ($alreadyApplied) {
                return ResponseService::validationError(__('You have already applied for this job.'));
            }
            $resumePath = null;
            if ($request->hasFile('resume')) {
                $resumePath = FileService::upload($request->resume, 'job_resume');
            }

            $application = JobApplication::create([
                'item_id' => $post->id,
                'user_id' => Auth::user()->id,
                'recruiter_id' => $post->user_id,
                'full_name' => $request->full_name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'resume' => $resumePath,
            ]);

            if (! empty($post->user_id)) {
                NotificationService::dispatchChunkedNotifications(
                    'New Job Application',
                    $request->full_name . ' applied for your job post: ' . $post->name,
                    'job-application',
                    ['item_id' => $post->id],
                    false,
                    array($post->user_id)
                );
            }

            return ResponseService::successResponse(__('Application submitted successfully.'), $application);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> applyJob');

            return ResponseService::errorResponse();
        }
    }

    /** Get Recruiter Applications */
    public function recruiterApplications(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'nullable|integer',
            'page' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = Auth::user();

            $applications = JobApplication::where('recruiter_id', $user->id)
                ->with('user:id,name,email', 'item:id,name');
            if (! empty($request->item_id)) {
                $applications->where('item_id', $request->item_id);
            }

            $applications = $applications->latest()->paginate();

            return ResponseService::successResponse(__('Recruiter applications fetched'), $applications);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> recruiterApplications');

            return ResponseService::errorResponse();
        }
    }

    /** Get My Job Applications */
    public function myJobApplications(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'nullable|integer',
            'page' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $user = Auth::user();

            $applications = JobApplication::where('user_id', $user->id);

            if (! empty($request->item_id)) {
                $applications->where('item_id', $request->item_id);
            }

            $applications = $applications->with([
                'item:id,name,user_id',
                'recruiter:id,name,email',
            ])
                ->latest()
                ->paginate();

            return ResponseService::successResponse(__('Your job applications fetched'), $applications);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> myJobApplications');

            return ResponseService::errorResponse();
        }
    }

    /** Update Job Application Status */
    public function updateJobStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'job_id' => 'required|exists:job_applications,id',
            'status' => 'required|in:accepted,rejected',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $user = Auth::user();
            $application = JobApplication::with('item')->findOrFail($request->job_id);

            if ($application->recruiter_id !== $user->id) {
                return ResponseService::errorResponse(__('Unauthorized to update this job status.'), 403);
            }

            $application->update(['status' => $request->status]);

            if (! empty($application->user_id)) {
                NotificationService::dispatchChunkedNotifications(
                    'Application ' . ucfirst($request->status),
                    'Your application for job post has been ' . $request->status,
                    'application-status',
                    ['job_id' => $application->id],
                    false,
                    array($application->user_id)
                );
            }

            return ResponseService::successResponse(__('Application status updated.'), $application);
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'API Controller -> updateJobStatus');

            return ResponseService::errorResponse();
        }
    }
}
