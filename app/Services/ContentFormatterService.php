<?php

namespace App\Services;

class ContentFormatterService
{
    /**
     * Regex pattern to identify 10-digit Indian mobile numbers.
     * Matches numbers starting with 6-9, with optional +91, 91, or 0 prefixes,
     * and optional spaces/hyphens between digits (e.g., 98765 43210, +91-98765-43210).
     */
    const INDIAN_MOBILE_REGEX = '/(?<!\d|\+)(?:\+?91[\s\-]?)?(?:0)?([6-9]\d{4})[\s\-]?(\d{5})(?!\d)/';

    /**
     * Regex pattern to identify raw URLs (http, https, and www).
     */
    const URL_REGEX = '/(?<!href=["\'])(?<!src=["\'])\b((?:https?:\/\/|www\.)[^\s<>"\'\)]+)/i';

    /**
     * Format an item/ad description with rich text sanitization,
     * clickable URLs, and clickable formatted Indian mobile numbers.
     * Supports both HTML strings and Quill Delta JSON formats.
     *
     * @param string|null $content
     * @return string
     */
    public static function formatItemDescription(?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        // Check if content is Quill Delta JSON
        $trimmed = trim($content);
        if (($trimmed[0] === '{' || $trimmed[0] === '[') && (strpos($trimmed, '"ops"') !== false || $trimmed[0] === '[')) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $ops = isset($decoded['ops']) ? $decoded['ops'] : (is_array($decoded) ? $decoded : null);
                if (!empty($ops) && is_array($ops)) {
                    $content = self::convertQuillDeltaToHtml($ops);
                }
            }
        }

        // 1. Sanitize HTML to prevent XSS while keeping rich text markup
        $safeHtml = self::sanitizeHtml($content);

        // 2. Linkify URLs that are not already inside an <a> tag
        $withLinks = self::makeUrlsClickable($safeHtml);

        // 3. Format and linkify Indian mobile numbers
        $formatted = self::makeIndianMobileNumbersClickable($withLinks);

        return $formatted;
    }

    /**
     * Convert any rich text or HTML string into pure plain text.
     * Strips all HTML tags and removes any formatting, ensuring no <p> or <strong> tags leak.
     *
     * @param string|null $content
     * @return string
     */
    public static function cleanPlainText(?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        $trimmed = trim($content);
        // If Quill JSON Delta, extract plain text from ops
        if (($trimmed[0] === '{' || $trimmed[0] === '[') && strpos($trimmed, '"ops"') !== false) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($decoded['ops'])) {
                $text = '';
                foreach ($decoded['ops'] as $op) {
                    if (isset($op['insert']) && is_string($op['insert'])) {
                        $text .= $op['insert'];
                    }
                }
                return trim($text);
            }
        }

        // Replace <br> and paragraph endings with newlines
        $normalized = preg_replace('/<br\s*\/?>/i', "\n", $content);
        $normalized = preg_replace('/<\/p>/i', "\n\n", $normalized);
        $normalized = preg_replace('/<\/li>/i', "\n", $normalized);

        // Strip remaining HTML tags
        $plain = strip_tags($normalized);

        // Decode HTML entities (e.g. &nbsp; &amp;)
        $plain = html_entity_decode($plain, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize multiple spaces/newlines
        $plain = preg_replace("/\n{3,}/", "\n\n", $plain);

        return trim($plain);
    }

    /**
     * Convert Quill Delta ops array into formatted HTML.
     * Supports bold, italic, underline, strike, color, background, link, and headers.
     *
     * @param array $ops
     * @return string
     */
    public static function convertQuillDeltaToHtml(array $ops): string
    {
        $html = '';
        foreach ($ops as $op) {
            if (!isset($op['insert'])) continue;

            $insert = $op['insert'];

            // Handle embedded image
            if (is_array($insert) && isset($insert['image'])) {
                $imgUrl = htmlspecialchars($insert['image'], ENT_QUOTES, 'UTF-8');
                $html .= '<p><img src="' . $imgUrl . '" class="img-fluid rounded my-2" style="max-width: 100%; height: auto;" alt="Item image"></p>';
                continue;
            }

            if (!is_string($insert)) continue;

            $attrs = $op['attributes'] ?? [];
            $text = htmlspecialchars($insert, ENT_QUOTES, 'UTF-8');

            // Apply inline styles and tags
            $style = '';
            if (!empty($attrs['color'])) {
                $style .= 'color: ' . htmlspecialchars($attrs['color'], ENT_QUOTES, 'UTF-8') . '; ';
            }
            if (!empty($attrs['background'])) {
                $style .= 'background-color: ' . htmlspecialchars($attrs['background'], ENT_QUOTES, 'UTF-8') . '; padding: 2px 4px; border-radius: 4px; ';
            }

            if (!empty($style)) {
                $text = '<span style="' . trim($style) . '">' . $text . '</span>';
            }

            if (!empty($attrs['bold'])) {
                $text = '<strong>' . $text . '</strong>';
            }
            if (!empty($attrs['italic'])) {
                $text = '<em>' . $text . '</em>';
            }
            if (!empty($attrs['underline'])) {
                $text = '<u>' . $text . '</u>';
            }
            if (!empty($attrs['strike'])) {
                $text = '<s>' . $text . '</s>';
            }
            if (!empty($attrs['link'])) {
                $linkHref = htmlspecialchars($attrs['link'], ENT_QUOTES, 'UTF-8');
                $text = '<a href="' . $linkHref . '" target="_blank" rel="noopener noreferrer">' . $text . '</a>';
            }

            // Convert newlines to breaks or paragraphs
            $text = nl2br($text);

            $html .= $text;
        }

        return $html;
    }

    /**
     * Detect and format Indian mobile numbers into standard "+91 XXXXX XXXXX"
     * and make them clickable with tel: and WhatsApp action targets.
     *
     * @param string $html
     * @return string
     */
    public static function makeIndianMobileNumbersClickable(string $html): string
    {
        // Split HTML by existing <a> tags and HTML tags so we only replace in text nodes
        $parts = preg_split('/(<a\b[^>]*>.*?<\/a>|<[^>]+>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        $result = '';
        foreach ($parts as $part) {
            // If this part is an existing tag (especially an <a> tag), leave it untouched
            if (empty($part)) {
                continue;
            }

            if ($part[0] === '<') {
                $result .= $part;
                continue;
            }

            // In text node, replace 10-digit Indian mobile numbers
            $replaced = preg_replace_callback(self::INDIAN_MOBILE_REGEX, function ($matches) {
                $firstPart = $matches[1];
                $secondPart = $matches[2];
                $tenDigit = $firstPart . $secondPart;
                $displayFormat = "+91 {$firstPart} {$secondPart}";
                $telUrl = "tel:+91{$tenDigit}";
                $waUrl = "https://wa.me/91{$tenDigit}";

                return '<a href="' . $telUrl . '" class="eclassify-phone-link text-decoration-none font-weight-bold" '
                    . 'data-phone="' . $tenDigit . '" data-whatsapp="' . $waUrl . '" '
                    . 'title="Call ' . $displayFormat . ' | Chat on WhatsApp" '
                    . 'target="_blank" rel="noopener noreferrer">'
                    . '<span class="eclassify-phone-badge"><i class="bi bi-telephone-fill me-1"></i>' . $displayFormat . '</span>'
                    . '</a>';
            }, $part);

            $result .= $replaced;
        }

        return $result;
    }

    /**
     * Detect raw URLs and convert them to secure clickable <a> tags.
     *
     * @param string $html
     * @return string
     */
    public static function makeUrlsClickable(string $html): string
    {
        // Split HTML by existing <a> tags and HTML tags to prevent re-wrapping existing links
        $parts = preg_split('/(<a\b[^>]*>.*?<\/a>|<[^>]+>)/is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        $result = '';
        foreach ($parts as $part) {
            if (empty($part)) {
                continue;
            }

            if ($part[0] === '<') {
                $result .= $part;
                continue;
            }

            // In text node, replace raw URLs
            $replaced = preg_replace_callback(self::URL_REGEX, function ($matches) {
                $rawUrl = $matches[1];
                $href = $rawUrl;

                // Prepend https:// if starts with www.
                if (stripos($href, 'www.') === 0) {
                    $href = 'https://' . $href;
                }

                // Trim trailing punctuation if any (like dots or commas at end of sentence)
                $trailing = '';
                if (preg_match('/([.,;:!?]+)$/', $rawUrl, $puncMatches)) {
                    $trailing = $puncMatches[1];
                    $rawUrl = substr($rawUrl, 0, -strlen($trailing));
                    $href = substr($href, 0, -strlen($trailing));
                }

                return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" '
                    . 'class="eclassify-url-link text-primary text-decoration-underline" '
                    . 'target="_blank" rel="noopener noreferrer nofollow">'
                    . htmlspecialchars($rawUrl, ENT_QUOTES, 'UTF-8')
                    . '</a>' . $trailing;
            }, $part);

            $result .= $replaced;
        }

        return $result;
    }

    /**
     * Extract all unique 10-digit Indian mobile numbers found in content.
     *
     * @param string|null $content
     * @return array
     */
    public static function extractIndianMobileNumbers(?string $content): array
    {
        if (empty($content)) {
            return [];
        }

        $stripped = strip_tags($content);
        preg_match_all(self::INDIAN_MOBILE_REGEX, $stripped, $matches);

        if (empty($matches[1])) {
            return [];
        }

        $numbers = [];
        for ($i = 0; $i < count($matches[1]); $i++) {
            $tenDigit = $matches[1][$i] . $matches[2][$i];
            $formatted = "+91 " . $matches[1][$i] . " " . $matches[2][$i];
            $numbers[] = [
                'number' => $tenDigit,
                'formatted' => $formatted,
                'tel_link' => "tel:+91{$tenDigit}",
                'whatsapp_link' => "https://wa.me/91{$tenDigit}",
            ];
        }

        // Return unique by number
        $unique = [];
        foreach ($numbers as $num) {
            $unique[$num['number']] = $num;
        }

        return array_values($unique);
    }

    /**
     * Extract all unique URLs found in content.
     *
     * @param string|null $content
     * @return array
     */
    public static function extractUrls(?string $content): array
    {
        if (empty($content)) {
            return [];
        }

        preg_match_all(self::URL_REGEX, $content, $matches);

        if (empty($matches[1])) {
            return [];
        }

        $urls = [];
        foreach ($matches[1] as $url) {
            $cleaned = rtrim($url, '.,;:!?');
            $href = stripos($cleaned, 'www.') === 0 ? 'https://' . $cleaned : $cleaned;
            $urls[] = [
                'display' => $cleaned,
                'url' => $href,
            ];
        }

        $unique = [];
        foreach ($urls as $item) {
            $unique[$item['url']] = $item;
        }

        return array_values($unique);
    }

    /**
     * Sanitize HTML to prevent Cross-Site Scripting (XSS).
     * Preserves standard rich text tags and attributes while stripping harmful code.
     *
     * @param string $html
     * @return string
     */
    public static function sanitizeHtml(string $html): string
    {
        // 1. Strip script, style, iframe, object, embed, applet, form tags and contents
        $sanitized = preg_replace('/<(script|style|iframe|object|embed|applet|form)[^>]*>.*?<\/\1>/si', '', $html);
        $sanitized = preg_replace('/<(script|style|iframe|object|embed|applet|form)[^>]*>/si', '', $sanitized);

        // 2. Remove inline event handlers (onclick, onload, onerror, etc.)
        $sanitized = preg_replace('/\s*on[a-zA-Z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $sanitized);

        // 3. Remove javascript: and data: in href or src
        $sanitized = preg_replace('/href\s*=\s*["\']\s*(?:javascript|data|vbscript):[^"\']*["\']/i', 'href="#"', $sanitized);
        $sanitized = preg_replace('/src\s*=\s*["\']\s*(?:javascript|data|vbscript):[^"\']*["\']/i', 'src="#"', $sanitized);

        return trim($sanitized);
    }
}
