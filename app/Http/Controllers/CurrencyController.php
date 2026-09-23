<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Currency;
use App\Services\BootstrapTableService;
use App\Services\CachingService;
use App\Services\ResponseService;
// use Illuminate\Support\Facades\Request;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CurrencyController extends Controller
{
    public function index()
    {
        ResponseService::noAnyPermissionThenRedirect(['currency-list', 'currency-create', 'currency-update', 'currency-delete']);
        $currencies = Currency::all();

        return view('currency.index', compact('currencies'));
    }

    public function create()
    {
        ResponseService::noPermissionThenRedirect('currency-create');
        $countries = Country::all();

        return view('currency.create', compact('countries'));
    }

    public function getCountryCurrencyData(Country $country)
    {
        ResponseService::noPermissionThenSendJson('currency-create');

        return ResponseService::successResponse('', [
            'iso_code' => $country->currency,
            'name' => $country->currency_name,
            'symbol' => $country->currency_symbol,
            'currency_exists' => Currency::where('country_id', $country->id)->exists(),
        ]);
    }

    public function show(Request $request)
    {
        ResponseService::noPermissionThenSendJson('currency-list');

        $offset = $request->offset ?? 0;
        $limit  = $request->limit ?? 10;
        $sort   = $request->sort ?? 'id';
        $order  = $request->order ?? 'DESC';

        $sql = Currency::with('country:id,name');

        if (! empty($request->search)) {
            $sql->search($request->search);
        }

        $total = $sql->count();

        $sql->sort($sort, $order)
            ->skip($offset)
            ->take($limit);

        $result = $sql->get();

        $rows = [];
        foreach ($result as $row) {
            $tempRow = $row->toArray();
            $operate = '';

            if (Auth::user()->can('currency-update')) {
                $operate .= BootstrapTableService::editButton(route('currency.edit', $row->id));
            }

            if (Auth::user()->can('currency-delete')) {
                $operate .= BootstrapTableService::deleteButton(route('currency.destroy', $row->id));
            }

            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        return response()->json([
            'total' => $total,
            'rows'  => $rows,
        ]);
    }


    public function store(Request $request)
    {
        ResponseService::noPermissionThenSendJson('currency-create');

        $validator = Validator::make($request->all(), [
            'iso_code' => 'required|string|regex:/^[a-zA-Z]+$/|unique:currencies,iso_code',
            'name' => 'required|string|unique:currencies,name',
            'symbol' => 'required|string',
            'symbol_position' => 'required|in:left,right',
            'country_id' => 'required|integer',
        ], [
            'iso_code.regex' => trans('Only characters are allowed for the ISO code.'),
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            Currency::create([
                'iso_code' => strtoupper($request->iso_code),
                'name' => $request->name,
                'symbol' => $request->symbol,
                'symbol_position' => $request->symbol_position,
                'country_id' => $request->country_id,
                'decimal_places' => $request->decimal_places,
                'thousand_separator' => $this->normalizeSeparator($request->thousand_separator),
                'decimal_separator' => $this->normalizeSeparator($request->decimal_separator),
            ]);

            return ResponseService::successResponse(
                __('Currency Created Successfully'),
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Currency Controller -> store');
            ResponseService::errorResponse(__($th->getMessage()));
        }
    }

    public function edit($id)
    {
        ResponseService::noPermissionThenRedirect('currency-update');
        $currency = Currency::findOrFail($id);
        $languages = CachingService::getLanguages()->values();
        $countries = Country::all();

        return view('currency.edit', compact('currency', 'languages', 'countries'));
    }

    public function update(Request $request, $id)
    {
        ResponseService::noPermissionThenSendJson('currency-update');

        // dd($request->all());

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'symbol' => 'required',
            'iso_code' => 'required|string|regex:/^[a-zA-Z]+$/|unique:currencies,iso_code,' . $id,
            'symbol_position' => 'required',
        ], [
            'iso_code.regex' => trans('Only characters are allowed for the ISO code.'),
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $currency = Currency::findOrFail($id);

            $data = $request->all();
            $data['thousand_separator'] = $this->normalizeSeparator($request->thousand_separator);
            $data['decimal_separator'] = $this->normalizeSeparator($request->decimal_separator);

            $currency->update($data);

            return ResponseService::successRedirectResponse(
                __('Currency Updated Successfully'),
                route('currency.index')
            );
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Currency Controller -> update');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }

    private function normalizeSeparator($value)
    {
        return trim((string) $value) === '' ? "\u{202F}" : $value;
    }

    public function destroy($id)
    {
        ResponseService::noPermissionThenSendJson('currency-delete');
        try {
            $currency = Currency::findOrFail($id);
            $currency->delete();
            ResponseService::successResponse('Currency Deleted Successfully');
        } catch (Throwable $th) {
            ResponseService::logErrorResponse($th, 'Currency Controller -> destroy');
            ResponseService::errorResponse('Something Went Wrong');
        }
    }
}
