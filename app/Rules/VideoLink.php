<?php
 
namespace App\Rules;
 
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
 
class VideoLink implements ValidationRule
{
    protected $videoType;

    /**
     * Create a new rule instance.
     *
     * @param string|null $videoType
     */
    public function __construct($videoType)
    {
        $this->videoType = $videoType;
    }

    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->videoType === 'youtube_link') {
            $youtubePattern = '/^(https?:\/\/)?(www\.|m\.)?(youtube\.com|youtu\.be|youtube-nocookie\.com)\/.+$/i';
            if (!preg_match($youtubePattern, $value)) {
                $fail(__('The video link must be a valid YouTube URL.'));
                return;
            }

            // Transform youtu.be short URL or shorts URL to full YouTube URL for validation
            $checkUrl = $value;
            if (strpos($checkUrl, 'youtu.be') !== false) {
                $path = parse_url($checkUrl, PHP_URL_PATH);
                $checkUrl = 'https://www.youtube.com/watch?v=' . substr($path, 1);
            } elseif (strpos($checkUrl, '/shorts/') !== false) {
                $path = parse_url($checkUrl, PHP_URL_PATH);
                $parts = explode('/', trim($path, '/'));
                $videoId = end($parts);
                $checkUrl = 'https://www.youtube.com/watch?v=' . $videoId;
            }

            try {
                $response = Http::timeout(5)
                    ->withOptions(['verify' => false])
                    ->head($checkUrl);

                if (!$response->successful()) {
                    $response = Http::timeout(5)
                        ->withOptions(['verify' => false])
                        ->get($checkUrl);
                }

                if (!$response->successful()) {
                    $fail(__('The video link is not accessible.'));
                    return;
                }
            } catch (\Exception $e) {
                $fail(__('The video link could not be verified.'));
                return;
            }
        } elseif ($this->videoType === 'vimeo_link') {
            $vimeoPattern = '/^(https?:\/\/)?(www\.|player\.)?vimeo\.com\/(video\/)?[0-9]{8,}([?#][^\s]*)?$/i';
            if (!preg_match($vimeoPattern, $value)) {
                $fail(__('The video link must be a valid Vimeo URL.'));
                return;
            }

            try {
                $response = Http::timeout(5)
                    ->withOptions(['verify' => false])
                    ->head($value);

                if (!$response->successful()) {
                    $response = Http::timeout(5)
                        ->withOptions(['verify' => false])
                        ->get($value);
                }

                if (!$response->successful()) {
                    $fail(__('The video link is not accessible.'));
                    return;
                }
            } catch (\Exception $e) {
                $fail(__('The video link could not be verified.'));
                return;
            }
        } elseif ($this->videoType === 'other_link') {
            $host = parse_url($value, PHP_URL_HOST);
            if (!$host) {
                $fail(__('The video link must be a valid direct video URL.'));
                return;
            }

            $hostClean = preg_replace('/^(www\.|m\.)/', '', strtolower($host));
            $isYoutube = in_array($hostClean, ['youtube.com', 'youtu.be', 'youtube-nocookie.com']);

            $hostVimeo = preg_replace('/^(www\.)/', '', strtolower($host));
            $isVimeo = in_array($hostVimeo, ['vimeo.com', 'player.vimeo.com']);

            if ($isYoutube || $isVimeo) {
                $fail(__('The video link must not be a YouTube or Vimeo URL.'));
                return;
            }

            $path = parse_url($value, PHP_URL_PATH) ?? '';
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $allowedExtensions = ['mp4', 'webm', 'ogg', 'ogv', 'mov', 'm3u8', 'mkv', 'avi'];

            if (!in_array($extension, $allowedExtensions)) {
                $fail(__('The video link must be a direct link to a video file (e.g. mp4, webm, m3u8).'));
                return;
            }

            try {
                $response = Http::timeout(5)
                    ->withOptions(['verify' => false])
                    ->head($value);

                if (!$response->successful()) {
                    $response = Http::timeout(5)
                        ->withOptions(['verify' => false])
                        ->withHeaders(['Range' => 'bytes=0-1023'])
                        ->get($value);
                }

                if (!$response->successful()) {
                    $fail(__('The video link is not accessible.'));
                    return;
                }

                $contentType = strtolower($response->header('Content-Type') ?? '');
                $isStreamableVideo = str_contains($contentType, 'video/') || 
                                     str_contains($contentType, 'mpegurl') || 
                                     str_contains($contentType, 'm3u8') ||
                                     str_contains($contentType, 'application/x-mpegurl');

                if (!$isStreamableVideo) {
                    $fail(__('The video link is not a streamable video content type.'));
                }
            } catch (\Exception $e) {
                $fail(__('The video link could not be verified.'));
            }
        } else {
            $fail(__('Video link is not allowed for the selected video type.'));
        }
    }
}
