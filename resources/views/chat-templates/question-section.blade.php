@php
    $title = $title ?? __('Questions');
    $subtitle = $subtitle ?? '';
    $field_base = $field_base ?? 'questions';
    $questions = $questions ?? []; // array of ['question' => defaultText, 'translations' => [langId => text]]
    $languages = $languages ?? collect();
    $default_lang_id = $default_lang_id ?? 1;
    $hint = $hint ?? __('Keep the message short and conversational.');
    $empty_text = $empty_text ?? __('No message added yet. Click "Add Message" to configure conversation starters.');
@endphp

<div class="cs-card ql-card" data-default-lang="{{ $default_lang_id }}">
    <div class="cs-header ql-header">
        <div>
            <p class="cs-title">{{ $title }}</p>
            <p class="cs-subtitle">{{ $subtitle }}</p>
        </div>
        <button type="button" class="ql-add-btn">
            <i class="ph ph-plus"></i> {{ __('Add Message') }}
        </button>
    </div>

    <div class="ql-body">
        <div class="ql-compose" style="display: none;">
            @if ($languages->count() > 1)
                <div class="ql-lang-tabs">
                    @foreach ($languages as $lang)
                        <button type="button" class="ql-lang-tab {{ $lang->id == $default_lang_id ? 'active' : '' }}"
                            data-lang-id="{{ $lang->id }}">{{ $lang->name }}</button>
                    @endforeach
                </div>
            @endif
            <label class="ql-compose-label">
                {{ __('Message Text') }}
                <span class="ql-compose-lang-name" data-default-lang-name="{{ $languages->firstWhere('id', $default_lang_id)->name ?? '' }}">
                    ({{ $languages->firstWhere('id', $default_lang_id)->name ?? '' }})
                </span>
            </label>
            <div class="ql-compose-row">
                <input type="text" class="ql-input" maxlength="1000" placeholder="{{ __('Enter message') }}">
                <button type="button" class="ql-confirm" aria-label="{{ __('Add') }}"><i class="ph-bold ph-plus"></i></button>
                <button type="button" class="ql-cancel" aria-label="{{ __('Cancel') }}"><i class="ph ph-x"></i></button>
            </div>
            <p class="ql-hint">{{ $hint }}</p>
        </div>

        <div class="ql-list" data-field-base="{{ $field_base }}" data-lang-ids="{{ $languages->pluck('id')->implode(',') }}">
            @foreach ($questions as $question)
                <div class="ql-row">
                    @foreach ($languages as $lang)
                        <input type="hidden" name="{{ $field_base }}[{{ $lang->id }}][]"
                            value="{{ $lang->id == $default_lang_id ? $question['question'] : ($question['translations'][$lang->id] ?? '') }}"
                            data-lang-id="{{ $lang->id }}">
                    @endforeach
                    <span class="ql-number"></span>
                    <span class="ql-text">{{ $question['question'] }}</span>
                    <span class="ql-actions">
                        <button type="button" class="ql-edit" aria-label="{{ __('Edit') }}"><i class="ph ph-pencil-simple-line"></i></button>
                        <button type="button" class="ql-delete" aria-label="{{ __('Delete') }}"><i class="ph ph-trash"></i></button>
                    </span>
                </div>
            @endforeach
        </div>
        <p class="ql-empty" style="display: {{ empty($questions) ? 'block' : 'none' }};">{{ $empty_text }}</p>
    </div>
</div>

@once
    <script>
        (function () {
            function renumber(list) {
                list.querySelectorAll('.ql-row').forEach(function (row, index) {
                    row.querySelector('.ql-number').textContent = index + 1;
                });
                var card = list.closest('.ql-card');
                var emptyText = card.querySelector('.ql-empty');
                if (emptyText) {
                    emptyText.style.display = list.querySelectorAll('.ql-row').length ? 'none' : 'block';
                }
            }

            function buildRow(fieldBase, langIds) {
                var row = document.createElement('div');
                row.className = 'ql-row';
                var html = '';
                langIds.forEach(function (langId) {
                    html += '<input type="hidden" name="' + fieldBase + '[' + langId + '][]" value="" data-lang-id="' + langId + '">';
                });
                html +=
                    '<span class="ql-number"></span>' +
                    '<span class="ql-text"></span>' +
                    '<span class="ql-actions">' +
                    '<button type="button" class="ql-edit" aria-label="Edit"><i class="ph ph-pencil-simple-line"></i></button>' +
                    '<button type="button" class="ql-delete" aria-label="Delete"><i class="ph ph-trash"></i></button>' +
                    '</span>';
                row.innerHTML = html;
                return row;
            }

            function applyValuesToRow(row, values, defaultLangId) {
                row.querySelectorAll('input[type=hidden]').forEach(function (input) {
                    input.value = values[input.dataset.langId] || '';
                });
                row.querySelector('.ql-text').textContent = values[defaultLangId] || '';
            }

            function readValuesFromRow(row) {
                var values = {};
                row.querySelectorAll('input[type=hidden]').forEach(function (input) {
                    values[input.dataset.langId] = input.value;
                });
                return values;
            }

            function initQuestionSection(card) {
                var addBtn = card.querySelector('.ql-add-btn');
                var compose = card.querySelector('.ql-compose');
                var input = card.querySelector('.ql-input');
                var confirmBtn = card.querySelector('.ql-confirm');
                var cancelBtn = card.querySelector('.ql-cancel');
                var list = card.querySelector('.ql-list');
                var fieldBase = list.dataset.fieldBase;
                var langIds = list.dataset.langIds.split(',');
                var defaultLangId = card.dataset.defaultLang;
                var langTabs = card.querySelectorAll('.ql-lang-tab');
                var langNameEl = card.querySelector('.ql-compose-lang-name');
                var editingRow = null;
                var draftValues = {};
                var activeLangId = defaultLangId;
                var composeAnchor = document.createComment('compose-anchor');
                compose.parentNode.insertBefore(composeAnchor, compose.nextSibling);

                function setActiveLang(langId) {
                    draftValues[activeLangId] = input.value;
                    activeLangId = String(langId);
                    input.value = draftValues[activeLangId] || '';
                    langTabs.forEach(function (tab) {
                        tab.classList.toggle('active', tab.dataset.langId === activeLangId);
                        if (tab.dataset.langId === activeLangId && langNameEl) {
                            langNameEl.textContent = '(' + tab.textContent.trim() + ')';
                        }
                    });
                    input.focus();
                }

                langTabs.forEach(function (tab) {
                    tab.addEventListener('click', function () { setActiveLang(tab.dataset.langId); });
                });

                function openCompose(row) {
                    if (editingRow) {
                        editingRow.style.display = '';
                    }
                    editingRow = row || null;
                    draftValues = editingRow ? readValuesFromRow(editingRow) : {};
                    activeLangId = defaultLangId;
                    input.value = draftValues[activeLangId] || '';
                    langTabs.forEach(function (tab) {
                        tab.classList.toggle('active', tab.dataset.langId === activeLangId);
                    });
                    if (langNameEl) {
                        var activeTab = card.querySelector('.ql-lang-tab[data-lang-id="' + activeLangId + '"]');
                        langNameEl.textContent = '(' + (activeTab ? activeTab.textContent.trim() : langNameEl.dataset.defaultLangName) + ')';
                    }

                    if (editingRow) {
                        editingRow.style.display = 'none';
                        editingRow.parentNode.insertBefore(compose, editingRow);
                    } else {
                        composeAnchor.parentNode.insertBefore(compose, composeAnchor);
                    }
                    compose.style.display = 'block';
                    addBtn.style.display = 'none';
                    confirmBtn.innerHTML = editingRow ? '<i class="ph-bold ph-check"></i>' : '<i class="ph-bold ph-plus"></i>';
                    input.focus();
                }

                function closeCompose() {
                    if (editingRow) {
                        editingRow.style.display = '';
                    }
                    editingRow = null;
                    draftValues = {};
                    input.value = '';
                    compose.style.display = 'none';
                    composeAnchor.parentNode.insertBefore(compose, composeAnchor);
                    addBtn.style.display = 'inline-flex';
                }

                addBtn.addEventListener('click', function () { openCompose(null); });
                cancelBtn.addEventListener('click', closeCompose);

                confirmBtn.addEventListener('click', function () {
                    draftValues[activeLangId] = input.value;
                    var defaultText = (draftValues[defaultLangId] || '').trim();
                    if (!defaultText) {
                        setActiveLang(defaultLangId);
                        input.focus();
                        return;
                    }
                    draftValues[defaultLangId] = defaultText;

                    if (editingRow) {
                        applyValuesToRow(editingRow, draftValues, defaultLangId);
                    } else {
                        var row = buildRow(fieldBase, langIds);
                        list.appendChild(row);
                        applyValuesToRow(row, draftValues, defaultLangId);
                    }
                    renumber(list);
                    closeCompose();
                });

                input.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        confirmBtn.click();
                    }
                });

                list.addEventListener('click', function (e) {
                    var editTarget = e.target.closest('.ql-edit');
                    if (editTarget) {
                        openCompose(editTarget.closest('.ql-row'));
                        return;
                    }
                    var deleteTarget = e.target.closest('.ql-delete');
                    if (deleteTarget) {
                        deleteTarget.closest('.ql-row').remove();
                        renumber(list);
                    }
                });

                renumber(list);
            }

            function initAll() {
                document.querySelectorAll('.ql-card').forEach(initQuestionSection);
            }

            window.hasUnsavedQuestionDraft = function () {
                var open = false;
                document.querySelectorAll('.ql-compose').forEach(function (compose) {
                    if (compose.style.display !== 'none') {
                        open = true;
                    }
                });
                return open;
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initAll);
            } else {
                initAll();
            }
        })();
    </script>
@endonce
