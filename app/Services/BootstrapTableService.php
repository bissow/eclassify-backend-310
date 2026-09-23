<?php

namespace App\Services;

class BootstrapTableService
{
    private static string $defaultClasses = 'btn icon btn-s btn-rounded btn-icon rounded-pill mb-1 btn-white';

    /**
     * SVG action icons keyed by type. Resolved at runtime from resources/svgs/general.
     */
    private static array $svgCache = [];

    public static function icon(string $type): string
    {
        $type = strtolower($type);
        $map = ['edit' => 'edit.svg', 'delete' => 'delete.svg', 'view' => 'view.svg'];
        if (!isset($map[$type])) {
            return '';
        }
        if (!isset(self::$svgCache[$type])) {
            $path = resource_path('svgs/general/' . $map[$type]);
            self::$svgCache[$type] = is_file($path) ? trim(file_get_contents($path)) : '';
        }
        return self::$svgCache[$type];
    }

    /**
     * Detect action type from legacy icon class so existing callers auto-upgrade to SVG.
     */
    private static function detectSvgType(string $iconClass): ?string
    {
        if (preg_match('/\b(svg-edit|fa-edit|fa-pen(cil)?)\b/', $iconClass)) {
            return 'edit';
        }
        if (preg_match('/\b(svg-delete|fa-trash)\b/', $iconClass)) {
            return 'delete';
        }
        if (preg_match('/\b(svg-view|fa-eye)\b/', $iconClass)) {
            return 'view';
        }
        return null;
    }

    private static function renderIcon(string $iconClass): string
    {
        $type = self::detectSvgType($iconClass);
        if ($type !== null) {
            $svg = self::icon($type);
            if ($svg !== '') {
                return $svg;
            }
        }
        return '<i class="' . $iconClass . '" width="20px" height="20px"></i>';
    }

    /**
     * @return string
     */
    public static function button(string $iconClass, string $url, array $customClass = [], array $customAttributes = [], string $iconText = '')
    {
        $customClassStr = implode(' ', $customClass);
        $type = self::detectSvgType($iconClass);
        $extra = $type !== null ? ' action-icon action-icon-' . $type : '';
        $class = self::$defaultClasses . $extra . ' ' . $customClassStr;
        $attributes = '';
        if (isset($customAttributes['title']) && !isset($customAttributes['data-bs-toggle'])) {
            $customAttributes['data-bs-toggle'] = 'tooltip';
            $customAttributes['data-bs-placement'] = $customAttributes['data-bs-placement'] ?? 'top';
        }
        if (count($customAttributes) > 0) {
            foreach ($customAttributes as $key => $value) {
                $attributes .= $key . '="' . htmlspecialchars((string) $value, ENT_QUOTES) . '" ';
            }
        }

        return '<a href="' . $url . '" class="' . $class . '" ' . $attributes . '>' . self::renderIcon($iconClass) . $iconText . '</a>&nbsp;&nbsp;';
    }

    public static function dropdown(
        string $iconClass,
        array $dropdownItems,
        array $customClass = [],
        array $customAttributes = []
    ) {
        $customClassStr = implode(' ', $customClass);
        $toggleClass = self::$defaultClasses . ' dropdown-toggle ' . $customClassStr;

        $attributes = '';
        if (count($customAttributes) > 0) {
            foreach ($customAttributes as $key => $value) {
                $attributes .= $key . '="' . htmlspecialchars((string) $value, ENT_QUOTES) . '" ';
            }
        }

        $dropdown = '<div class="dropdown d-inline-block">';
        $dropdown .= '<a class="' . $toggleClass . '" href="#" role="button" data-bs-toggle="dropdown" data-bs-popper="static" aria-expanded="false" ' . $attributes . '>';
        $dropdown .= self::renderIcon($iconClass);
        $dropdown .= '</a>';
        $dropdown .= '<ul class="dropdown-menu">';

        foreach ($dropdownItems as $item) {
            $icon = !empty($item['icon']) ? '<i class="' . $item['icon'] . '"></i> ' : '';
            $dropdown .= '<li><a class="dropdown-item" href="' . $item['url'] . '">' . $icon . $item['text'] . '</a></li>';
        }

        $dropdown .= '</ul>';
        $dropdown .= '</div>&nbsp;&nbsp;';

        return $dropdown;
    }

    /**
     * @param  string  $dataBsTarget
     * @param  null  $customClass
     * @param  null  $id
     * @param  string  $iconClass
     * @param  null  $onClick
     * @return string
     */
    public static function editButton($url, bool $modal = false, $dataBsTarget = '#editModal', $customClass = null, $id = null, $iconClass = 'ph ph-note-pencil', $onClick = null)
    {
        $customClass = [$customClass];
        $customAttributes = [
            'title' => trans('Edit'),
        ];
        if ($modal) {
            $customAttributes = [
                'title' => trans('Edit'),
                'data-bs-target' => $dataBsTarget,
                'data-bs-toggle' => 'modal',
                'id' => $id,
                'onclick' => $onClick,
            ];

            $customClass[] = 'edit_btn set-form-url';
        }

        return self::button($iconClass, $url, $customClass, $customAttributes);
    }

    /**
     * @param  null  $id
     * @param  null  $dataId
     * @param  null  $dataCategory
     * @param  null  $customClass
     * @return string
     */
    public static function deleteButton($url, $id = null, $dataId = null, $dataCategory = null, $customClass = null)
    {
        $customClass = ['delete-form' . $customClass];
        $customAttributes = [
            'title' => trans('Delete'),
            'id' => $id,
            'data-id' => $dataId,
            'data-category' => $dataCategory,
        ];
        $iconClass = 'ph ph-trash';

        return self::button($iconClass, $url, $customClass, $customAttributes);
    }

    /**
     * @return string
     */
    public static function restoreButton($url, string $title = 'Restore')
    {
        $customClass = ['btn-gradient-success', 'restore-data'];
        $customAttributes = [
            'title' => trans($title),
        ];
        $iconClass = 'fa fa-refresh';

        return self::button($iconClass, $url, $customClass, $customAttributes);
    }

    /**
     * @return string
     */
    public static function trashButton($url)
    {
        $customClass = ['btn-gradient-danger', 'trash-data'];
        $customAttributes = [
            'title' => trans('Delete Permanent'),
        ];
        $iconClass = 'fa fa-times';

        return self::button($iconClass, $url, $customClass, $customAttributes);
    }

    public static function optionButton($url)
    {
        $customClass = ['btn-option'];
        $customAttributes = [
            'title' => trans('View Option Data'),
        ];
        $iconClass = 'bi bi-gear';
        $iconText = ' Options';

        return self::button($iconClass, $url, $customClass, $customAttributes, $iconText);
    }
}
