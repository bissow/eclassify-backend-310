(function () {
    'use strict';

    // ==========================================================================
    // Fetch Interceptor for Sanctum cookie-based authentication
    // ==========================================================================
    const originalFetch = window.fetch;
    window.fetch = (url, options) => {
        const CSRF_TOKEN_COOKIE_KEY = "XSRF-TOKEN";
        const CSRF_TOKEN_HEADER_KEY = "X-XSRF-TOKEN";
        const getCookieValue = (key) => {
            const cookie = document.cookie.split(';').find((cookie) => cookie.trim().startsWith(key));
            return cookie?.split("=")[1];
        };

        const updateFetchHeaders = (headers, headerKey, headerValue) => {
            if (headers instanceof Headers) {
                headers.set(headerKey, headerValue);
            } else if (Array.isArray(headers)) {
                headers.push([headerKey, headerValue]);
            } else if (headers) {
                headers[headerKey] = headerValue;
            }
        };

        const isSameOrigin = (() => {
            try {
                return new URL(url, window.location.href).origin === window.location.origin;
            } catch (_) {
                return false;
            }
        })();

        const csrfToken = isSameOrigin ? getCookieValue(CSRF_TOKEN_COOKIE_KEY) : null;
        if (csrfToken) {
            const { headers = new Headers() } = options || {};
            updateFetchHeaders(headers, CSRF_TOKEN_HEADER_KEY, decodeURIComponent(csrfToken));
            return originalFetch(url, {
                ...options,
                headers,
            });
        }

        return originalFetch(url, options);
    };

    // ==========================================================================
    // Custom Interactive API Documentation Page Builder
    // ==========================================================================
    let endpointList = [];
    const API_BASE_URL_STORAGE_KEY = 'docs_api_base_url';

    function defaultApiBaseUrl() {
        return window.location.origin + '/api';
    }

    function getApiBaseUrl() {
        return localStorage.getItem(API_BASE_URL_STORAGE_KEY) || defaultApiBaseUrl();
    }

    function setApiBaseUrl(url) {
        const clean = (url || '').trim().replace(/\/+$/, '');
        localStorage.setItem(API_BASE_URL_STORAGE_KEY, clean || defaultApiBaseUrl());
    }

    function resolveSchema(schema, spec) {
        if (!schema) return null;
        if (schema.$ref) {
            const parts = schema.$ref.split('/');
            const schemaName = parts[parts.length - 1];
            if (spec && spec.components && spec.components.schemas && spec.components.schemas[schemaName]) {
                return resolveSchema(spec.components.schemas[schemaName], spec);
            }
        }
        return schema;
    }

    function getBodyFields(operation, spec) {
        if (!operation || !operation.requestBody || !operation.requestBody.content) {
            return [];
        }
        const content = operation.requestBody.content;
        const mediaTypes = ['application/json', 'multipart/form-data', 'application/x-www-form-urlencoded'];
        
        let chosenMediaType = mediaTypes.find(type => content[type] && content[type].schema);
        if (!chosenMediaType) {
            chosenMediaType = Object.keys(content).find(type => content[type].schema);
        }
        
        if (!chosenMediaType) return [];

        const resolvedSchema = resolveSchema(content[chosenMediaType].schema, spec);
        if (!resolvedSchema) return [];

        const properties = resolvedSchema.properties || {};
        const requiredList = resolvedSchema.required || [];

        const fields = [];
        for (const [name, prop] of Object.entries(properties)) {
            const resolvedProp = resolveSchema(prop, spec);
            fields.push({
                name: name,
                type: resolvedProp ? (resolvedProp.type || 'string') : 'string',
                description: resolvedProp ? (resolvedProp.description || '') : '',
                required: requiredList.includes(name),
                default: resolvedProp && resolvedProp.default !== undefined ? resolvedProp.default : '',
                enum: resolvedProp && Array.isArray(resolvedProp.enum) ? resolvedProp.enum : null,
                minimum: resolvedProp ? resolvedProp.minimum : undefined,
                maximum: resolvedProp ? resolvedProp.maximum : undefined,
                exclusiveMinimum: resolvedProp ? resolvedProp.exclusiveMinimum : undefined,
                exclusiveMaximum: resolvedProp ? resolvedProp.exclusiveMaximum : undefined,
                minLength: resolvedProp ? resolvedProp.minLength : undefined,
                maxLength: resolvedProp ? resolvedProp.maxLength : undefined,
                pattern: resolvedProp ? resolvedProp.pattern : undefined,
                format: resolvedProp ? resolvedProp.format : undefined,
                minItems: resolvedProp ? resolvedProp.minItems : undefined,
                maxItems: resolvedProp ? resolvedProp.maxItems : undefined,
                nullable: resolvedProp ? resolvedProp.nullable : undefined
            });
        }
        return fields;
    }

    function initApiBaseUrlBar() {
        const input = document.getElementById('api-base-url-input');
        const resetBtn = document.getElementById('api-base-url-reset');
        if (!input || !resetBtn) return;

        input.value = getApiBaseUrl();

        input.addEventListener('change', () => {
            setApiBaseUrl(input.value);
            input.value = getApiBaseUrl();
            document.querySelectorAll('.curl-box').forEach(box => box.dispatchEvent(new Event('refresh-curl')));
            syncActiveCardFromHash();
        });

        resetBtn.addEventListener('click', () => {
            localStorage.removeItem(API_BASE_URL_STORAGE_KEY);
            input.value = getApiBaseUrl();
            syncActiveCardFromHash();
        });
    }

    function relocateCustomContainer() {
        const container = document.getElementById('custom-api-cards-container');
        const tryMove = () => {
            const apiRow = document.querySelector('.sl-elements-api');
            if (apiRow && container && container.parentElement !== apiRow) {
                apiRow.appendChild(container);
                return true;
            }
            return false;
        };
        if (tryMove()) return;
        const observer = new MutationObserver(() => {
            if (tryMove()) observer.disconnect();
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    function initSingleApiRunner(spec) {
        const container = document.getElementById('custom-api-cards-container');
        if (!container || !spec || !spec.paths) return;

        endpointList = [];
        const methods = ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'];

        for (const [path, pathItem] of Object.entries(spec.paths)) {
            for (const method of methods) {
                if (!pathItem[method]) continue;
                const op = pathItem[method];
                endpointList.push({
                    id: `${method.toUpperCase()} ${path}`,
                    method: method.toUpperCase(),
                    path: path,
                    operation: op,
                    operationId: op.operationId || ''
                });
            }
        }

        if (endpointList.length === 0) return;

        window.addEventListener('hashchange', syncActiveCardFromHash);
        syncActiveCardFromHash();
    }

    function syncActiveCardFromHash() {
        const rawHash = decodeURIComponent(window.location.hash || '');
        const hash = rawHash.toLowerCase();

        if (!hash || hash === '#/' || hash === '#') {
            renderOverviewCard();
            return;
        }

        const cleanHash = hash.replace(/[^a-z0-9]/g, '');

        // 1. Exact operationId match
        let found = endpointList.find(ep => {
            if (!ep.operationId) return false;
            const opId = ep.operationId.toLowerCase();
            return hash === `#/operations/${opId}` || hash.endsWith(`/${opId}`);
        });

        // 2. Direct path and method match in decoded hash
        if (!found) {
            found = endpointList.find(ep => {
                const epPath = ep.path.toLowerCase();
                const epMethod = ep.method.toLowerCase();
                return hash.includes(epPath) && (hash.includes(epMethod) || hash.endsWith(epPath));
            });
        }

        // 3. Normalized alphanumeric fallback
        if (!found) {
            found = endpointList.find(ep => {
                const cleanPath = ep.path.toLowerCase().replace(/[^a-z0-9]/g, '');
                const cleanMethod = ep.method.toLowerCase();
                return cleanHash.includes(cleanMethod) && cleanHash.includes(cleanPath);
            });
        }

        if (found) {
            renderSelectedCard(found);
        } else {
            renderOverviewCard();
        }
    }

    function renderOverviewCard() {
        const container = document.getElementById('custom-api-cards-container');
        if (!container) return;
        container.querySelectorAll('.api-grid-container, .response-box-full').forEach(el => el.remove());

        const wrap = document.createElement('div');
        wrap.className = 'api-grid-container overview-card';
        wrap.innerHTML = `
            <div class="api-info-panel" style="grid-column: 1 / -1;">
                <h1 class="api-info-title">Welcome to the API Docs</h1>
                <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 20px;">
                    Select an endpoint from the sidebar to view its details and test it out.
                </p>
                <h2 class="api-info-section-title">Getting Started</h2>
                <ul style="font-size: 14px; color: var(--text-main); line-height: 1.8; padding-left: 20px;">
                    <li>Set your <strong>API Base URL</strong> above — it applies to every request on this page.</li>
                    <li>Pick an endpoint from the left sidebar.</li>
                    <li>Fill in headers/params, then hit <strong>Send API Request</strong> to test it live.</li>
                </ul>
            </div>
        `;
        container.appendChild(wrap);
    }

    function renderSelectedCard(activeEndpoint) {
        const container = document.getElementById('custom-api-cards-container');
        if (!container) return;
        container.querySelectorAll('.api-grid-container, .response-box-full').forEach(el => el.remove());

        const gridContainer = document.createElement('div');
        gridContainer.className = 'api-grid-container';

        const infoPanel = createApiInfoPanel(activeEndpoint);
        gridContainer.appendChild(infoPanel);

        const responseBox = createResponseBox();

        const card = createApiCard(activeEndpoint.method, activeEndpoint.path, activeEndpoint.operation, responseBox);
        gridContainer.appendChild(card);

        container.appendChild(gridContainer);
        container.appendChild(responseBox);
    }

    function createResponseBox() {
        const box = document.createElement('div');
        box.className = 'response-box-full';
        box.style.display = 'none';
        box.innerHTML = `
            <div class="card-section-title curl-title-row">
                <span>RESPONSE</span>
                <button class="btn-copy-response" type="button">Copy</button>
            </div>
            <div class="response-status"></div>
            <pre class="response-body"></pre>
        `;

        const copyBtn = box.querySelector('.btn-copy-response');
        copyBtn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(box.querySelector('.response-body').textContent);
                const original = copyBtn.textContent;
                copyBtn.textContent = 'Copied!';
                setTimeout(() => { copyBtn.textContent = original; }, 1500);
            } catch (e) {
                console.error('Copy failed', e);
            }
        });

        return box;
    }

    function formatSchemaMeta(schema) {
        if (!schema) return '';
        const parts = [];
        if (Array.isArray(schema.enum) && schema.enum.length) {
            const vals = schema.enum.filter(v => v !== null);
            if (vals.length) parts.push(`Allowed: ${vals.join(', ')}`);
        }
        if (schema.minimum !== undefined) parts.push(`min: ${schema.minimum}`);
        if (schema.maximum !== undefined) parts.push(`max: ${schema.maximum}`);
        if (schema.exclusiveMinimum !== undefined) parts.push(`> ${schema.exclusiveMinimum}`);
        if (schema.exclusiveMaximum !== undefined) parts.push(`< ${schema.exclusiveMaximum}`);
        if (schema.minLength !== undefined) parts.push(`min length: ${schema.minLength}`);
        if (schema.maxLength !== undefined) parts.push(`max length: ${schema.maxLength}`);
        if (schema.pattern) parts.push(`pattern: ${schema.pattern}`);
        if (schema.format) parts.push(`format: ${schema.format}`);
        if (schema.minItems !== undefined) parts.push(`min items: ${schema.minItems}`);
        if (schema.maxItems !== undefined) parts.push(`max items: ${schema.maxItems}`);
        return parts.join(' · ');
    }

    function createApiInfoPanel(ep) {
        const panel = document.createElement('div');
        panel.className = 'api-info-panel';

        const op = ep.operation;
        const isGetLike = ['GET', 'HEAD', 'DELETE', 'OPTIONS'].includes(ep.method);
        const specParams = op.parameters || [];

        let paramsHtml = '';
        if (isGetLike) {
            specParams.forEach(p => {
                const pType = p.schema?.type || 'string';
                const isNullable = p.schema?.nullable ? ' or null' : '';
                const desc = p.description || p.schema?.description || '';
                const meta = formatSchemaMeta(p.schema);
                paramsHtml += `
                    <div class="param-doc-item">
                        <div class="param-doc-item-row">
                            <span class="param-doc-name">${p.name}</span>
                            <span class="param-doc-type">${pType}${isNullable}</span>
                        </div>
                        ${desc ? `<div class="param-doc-desc">${desc}</div>` : ''}
                        ${meta ? `<div class="param-doc-desc">${meta}</div>` : ''}
                    </div>
                `;
            });
        } else {
            const bodyFields = getBodyFields(op, window.globalSpec);
            bodyFields.forEach(f => {
                const isNullable = f.nullable ? ' or null' : '';
                const isRequired = f.required ? ' <span style="color:#ef4444">*</span>' : '';
                const desc = f.description || '';
                const meta = formatSchemaMeta(f);
                paramsHtml += `
                    <div class="param-doc-item">
                        <div class="param-doc-item-row">
                            <span class="param-doc-name">${f.name}${isRequired}</span>
                            <span class="param-doc-type">${f.type}${isNullable}</span>
                        </div>
                        ${desc ? `<div class="param-doc-desc">${desc}</div>` : ''}
                        ${meta ? `<div class="param-doc-desc">${meta}</div>` : ''}
                    </div>
                `;
            });
        }

        if (!paramsHtml) {
            paramsHtml = '<div style="color: var(--text-muted); font-size: 13px;">No parameters required</div>';
        }

        panel.innerHTML = `
            <h1 class="api-info-title">${op.summary || op.description || ep.path}</h1>
            <div class="api-path-badge-wrap">
                <span class="method-badge method-${ep.method}">${ep.method}</span>
                <span style="font-family: var(--font-code); font-size: 14px;">${ep.path}</span>
            </div>

            ${op.description ? `<p style="font-size: 14px; color: var(--text-muted); margin-bottom: 20px;">${op.description}</p>` : ''}

            <h2 class="api-info-section-title">Request</h2>
            <div style="font-weight: 600; font-size: 14px; margin-bottom: 10px; color: var(--text-main);">${isGetLike ? 'Query Parameters' : 'Body Fields'}</div>
            <div class="param-doc-list">${paramsHtml}</div>

            <h2 class="api-info-section-title">Responses</h2>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="response-badge">200</span>
                <span style="font-size: 13px; color: var(--text-muted);">OK</span>
            </div>
        `;

        return panel;
    }

    function createApiCard(method, path, operation, responseBoxEl) {
        const card = document.createElement('div');
        card.className = 'api-card';

        const isGetLike = ['GET', 'HEAD', 'DELETE', 'OPTIONS'].includes(method);

        let headersState = [];
        let paramsState = [];

        headersState.push({ key: 'Accept', value: 'application/json', fixed: false, included: true });
        if (!isGetLike) {
            headersState.push({ key: 'Content-Type', value: 'application/json', fixed: false, included: true });
        }

        const specParams = operation.parameters || [];
        specParams.forEach(p => {
            if (p.in === 'header') {
                headersState.push({ key: p.name, value: p.schema?.default || '', fixed: p.required || false, included: p.required || false });
            } else if (p.in === 'query') {
                paramsState.push({ key: p.name, value: p.schema?.default || '', fixed: p.required || false, included: p.required || false });
            }
        });

        // Load custom headers from localStorage
        let storedHeaders = {};
        try {
            storedHeaders = JSON.parse(localStorage.getItem('docs_custom_headers')) || {};
        } catch(e) {}

        // Apply stored values to existing headers
        headersState.forEach(h => {
            if (storedHeaders[h.key] !== undefined) {
                const sObj = storedHeaders[h.key];
                if (typeof sObj === 'object' && sObj !== null) {
                    h.value = sObj.value;
                    h.included = sObj.included;
                } else {
                    h.value = sObj;
                    h.included = true;
                }
            }
        });

        // Add any stored headers that aren't already in headersState
        for (const [sKey, sObj] of Object.entries(storedHeaders)) {
            if (!headersState.find(h => h.key === sKey)) {
                if (typeof sObj === 'object' && sObj !== null) {
                    headersState.push({ key: sKey, value: sObj.value, fixed: false, included: sObj.included });
                } else {
                    headersState.push({ key: sKey, value: sObj, fixed: false, included: true });
                }
            }
        }

        if (!isGetLike) {
            const bodyFields = getBodyFields(operation, window.globalSpec);
            bodyFields.forEach(f => {
                paramsState.push({ key: f.name, value: f.default || '', fixed: f.required || false, included: f.required || false });
            });
        } else {
            specParams.forEach(p => {
                if (p.in !== 'header' && p.in !== 'query') {
                    paramsState.push({ key: p.name, value: p.schema?.default || '', fixed: p.required || false, included: p.required || false });
                }
            });
        }

        card.innerHTML = `
            <div class="card-section">
                <div class="card-section-title">HEADERS (FIXED & CUSTOM)</div>
                <div class="kv-grid headers-grid"></div>
                <button class="btn-add btn-add-header">+ Add Header</button>
            </div>

            <div class="card-section">
                <div class="card-section-title">${isGetLike ? 'QUERY PARAMETERS' : 'BODY FIELDS'}</div>
                <div class="kv-grid params-grid"></div>
                <button class="btn-add btn-add-param">+ Add ${isGetLike ? 'Param' : 'Field'}</button>
            </div>

            <div class="card-section">
                <div class="card-section-title curl-title-row">
                    <span>LIVE CURL COMMAND</span>
                    <button class="btn-copy-curl" type="button">Copy</button>
                </div>
                <div class="curl-box"></div>
            </div>

            <button class="btn-send">Send API Request</button>
        `;

        const headersGrid = card.querySelector('.headers-grid');
        const paramsGrid = card.querySelector('.params-grid');
        const curlBox = card.querySelector('.curl-box');
        const copyCurlBtn = card.querySelector('.btn-copy-curl');
        const sendBtn = card.querySelector('.btn-send');
        const responseBox = responseBoxEl;
        const responseStatus = responseBoxEl.querySelector('.response-status');
        const responseBody = responseBoxEl.querySelector('.response-body');

        copyCurlBtn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(curlBox.textContent);
                const original = copyCurlBtn.textContent;
                copyCurlBtn.textContent = 'Copied!';
                setTimeout(() => { copyCurlBtn.textContent = original; }, 1500);
            } catch (e) {
                console.error('Copy failed', e);
            }
        });

        function renderRows(containerEl, itemsList) {
            containerEl.innerHTML = '';
            itemsList.forEach((item, index) => {
                const row = document.createElement('div');
                row.className = 'kv-row' + (item.included ? '' : ' kv-row-excluded');
                row.innerHTML = `
                    <input type="checkbox" class="kv-include" ${item.included ? 'checked' : ''} ${item.fixed ? 'disabled' : ''} title="Include in request" />
                    <input type="text" class="kv-input key-input" placeholder="Name" value="${item.key}" ${item.fixed ? 'readonly' : ''} />
                    <input type="text" class="kv-input value-input" placeholder="Value" value="${item.value}" />
                    <button class="btn-remove" title="Remove">&times;</button>
                `;

                const includeInput = row.querySelector('.kv-include');
                const keyInput = row.querySelector('.key-input');
                const valInput = row.querySelector('.value-input');
                const removeBtn = row.querySelector('.btn-remove');

                includeInput.addEventListener('change', (e) => {
                    item.included = e.target.checked;
                    row.classList.toggle('kv-row-excluded', !item.included);
                    updateCurl();
                });

                keyInput.addEventListener('input', (e) => {
                    item.key = e.target.value;
                    updateCurl();
                });

                valInput.addEventListener('input', (e) => {
                    item.value = e.target.value;
                    item.included = true;
                    row.classList.remove('kv-row-excluded');
                    includeInput.checked = true;
                    updateCurl();
                });

                removeBtn.addEventListener('click', () => {
                    itemsList.splice(index, 1);
                    renderAll();
                });

                containerEl.appendChild(row);
            });
        }

        function buildRequestData() {
            let fullUrl = getApiBaseUrl() + path;

            const activeParams = paramsState.filter(p => p.key.trim() !== '' && p.included);
            if (isGetLike && activeParams.length > 0) {
                const queryStr = activeParams.map(p => `${encodeURIComponent(p.key)}=${encodeURIComponent(p.value)}`).join('&');
                fullUrl += '?' + queryStr;
            }

            const headersObj = {};
            headersState.filter(h => h.key.trim() !== '' && h.included).forEach(h => {
                headersObj[h.key] = h.value;
            });

            let bodyData = null;
            if (!isGetLike && activeParams.length > 0) {
                const payload = {};
                activeParams.forEach(p => payload[p.key] = p.value);
                bodyData = JSON.stringify(payload, null, 2);
            }

            return { url: fullUrl, headers: headersObj, body: bodyData };
        }

        function updateCurl() {
            const req = buildRequestData();
            let curl = `curl -X ${method} "${req.url}"`;

            for (const [hk, hv] of Object.entries(req.headers)) {
                curl += ` \\\n  -H "${hk}: ${hv}"`;
            }

            if (req.body) {
                curl += ` \\\n  -d '${req.body.replace(/\n/g, '')}'`;
            }

            curlBox.textContent = curl;

            // Sync all headers to global storage
            const storedToSave = {};
            headersState.forEach(h => {
                if (h.key.trim() !== '') {
                    storedToSave[h.key] = { value: h.value, included: h.included };
                }
            });
            try {
                localStorage.setItem('docs_custom_headers', JSON.stringify(storedToSave));
            } catch(e) {}
        }

        curlBox.addEventListener('refresh-curl', updateCurl);

        function renderAll() {
            renderRows(headersGrid, headersState);
            renderRows(paramsGrid, paramsState);
            updateCurl();
        }

        card.querySelector('.btn-add-header').addEventListener('click', () => {
            headersState.push({ key: '', value: '', fixed: false, included: true });
            renderAll();
        });

        card.querySelector('.btn-add-param').addEventListener('click', () => {
            paramsState.push({ key: '', value: '', fixed: false, included: true });
            renderAll();
        });

        sendBtn.addEventListener('click', async () => {
            sendBtn.disabled = true;
            sendBtn.textContent = 'Sending Request...';
            responseBox.style.display = 'none';

            const req = buildRequestData();

            try {
                const fetchOptions = {
                    method: method,
                    headers: req.headers
                };

                if (!isGetLike && req.body) {
                    fetchOptions.body = req.body;
                }

                const res = await window.fetch(req.url, fetchOptions);
                const text = await res.text();

                responseBox.style.display = 'block';
                responseStatus.textContent = `Status: ${res.status} ${res.statusText}`;
                responseStatus.style.color = res.ok ? '#10b981' : '#ef4444';

                try {
                    const parsed = JSON.parse(text);
                    responseBody.textContent = JSON.stringify(parsed, null, 2);
                } catch (_) {
                    responseBody.textContent = text || '(Empty Response)';
                }
            } catch (err) {
                responseBox.style.display = 'block';
                responseStatus.textContent = 'Error: Failed to fetch';
                responseStatus.style.color = '#ef4444';
                responseBody.textContent = err.message;
            } finally {
                sendBtn.disabled = false;
                sendBtn.textContent = 'Send API Request';
            }
        });

        renderAll();
        return card;
    }

    // ==========================================================================
    // System Theme Preference Tracker
    // ==========================================================================
    function setupSystemThemeTracker() {
        if (window.systemTheme === 'system') {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

            function updateTheme(e) {
                if (e.matches) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    const colorSchemeMeta = document.getElementsByName('color-scheme')[0];
                    if (colorSchemeMeta) colorSchemeMeta.setAttribute('content', 'dark');
                } else {
                    document.documentElement.setAttribute('data-theme', 'light');
                    const colorSchemeMeta = document.getElementsByName('color-scheme')[0];
                    if (colorSchemeMeta) colorSchemeMeta.setAttribute('content', 'light');
                }
            }

            mediaQuery.addEventListener('change', updateTheme);
            updateTheme(mediaQuery);
        }
    }

    // ==========================================================================
    // Drift Progress Tracker Widget JS Logic (WRTeam Custom Tracker)
    // ==========================================================================
    const DRIFT_STORAGE_KEY = 'drift-v1';
    const DRIFT_REMOVED_STORAGE_KEY = 'drift-removed-v1';
    const DRIFT_LEGACY_STORAGE_KEY = 'no-api-tracker-v1';
    const DRIFT_LEGACY_REMOVED_STORAGE_KEY = 'no-api-tracker-removed-v1';
    const DRIFT_METHODS = ['get', 'post', 'put', 'patch', 'delete', 'head', 'options'];

    const DRIFT_METHOD_VAR = {
        GET:     '--drift-green',
        POST:    '--drift-blue',
        PUT:     '--drift-orange',
        PATCH:   '--drift-orange',
        DELETE:  '--drift-red-text',
        HEAD:    '--drift-purple',
        OPTIONS: '--drift-purple',
    };

    let driftSpec = null;
    let driftSpecFetched = false;
    let driftState = { version: 1, endpoints: {} };
    let driftRemoved = [];
    let driftFilter = 'all';
    let driftLastFilter = driftFilter;
    let driftExpandedDiffs = new Set();
    let driftPanelOpen = false;

    function migrateLegacyStorage(oldKey, newKey) {
        try {
            if (localStorage.getItem(newKey) !== null) return;
            const legacy = localStorage.getItem(oldKey);
            if (legacy === null) return;
            localStorage.setItem(newKey, legacy);
            localStorage.removeItem(oldKey);
        } catch (_) {}
    }

    function loadDriftState() {
        migrateLegacyStorage(DRIFT_LEGACY_STORAGE_KEY, DRIFT_STORAGE_KEY);
        try {
            const raw = localStorage.getItem(DRIFT_STORAGE_KEY);
            if (raw) {
                const parsed = JSON.parse(raw);
                if (parsed && parsed.version === 1 && parsed.endpoints) driftState = parsed;
            }
        } catch (_) {}
    }

    function saveDriftState() {
        try { localStorage.setItem(DRIFT_STORAGE_KEY, JSON.stringify(driftState)); } catch (_) {}
    }

    function loadDriftRemoved() {
        migrateLegacyStorage(DRIFT_LEGACY_REMOVED_STORAGE_KEY, DRIFT_REMOVED_STORAGE_KEY);
        try {
            const raw = localStorage.getItem(DRIFT_REMOVED_STORAGE_KEY);
            if (raw) driftRemoved = JSON.parse(raw) || [];
        } catch (_) {}
    }

    function saveDriftRemoved() {
        try { localStorage.setItem(DRIFT_REMOVED_STORAGE_KEY, JSON.stringify(driftRemoved)); } catch (_) {}
    }

    function djb2(str) {
        let h = 5381;
        for (let i = 0; i < str.length; i++) h = ((h << 5) + h) ^ str.charCodeAt(i);
        return (h >>> 0).toString(16);
    }

    function snapshotBodyFields(op) {
        if (!op.requestBody || !op.requestBody.content) return [];
        return getBodyFields(op, driftSpec).map(function (f) {
            const type = f.type || 'string';
            return { name: f.name, required: !!f.required, type: Array.isArray(type) ? type.slice().sort().join(',') : type };
        });
    }

    function snapshotEndpoint(op) {
        return {
            summary:     op.summary || '',
            description: op.description || '',
            params:      (op.parameters || []).map(function (p) {
                             return { name: p.name, in: p.in, required: !!p.required };
                         }),
            hasBody:     !!op.requestBody,
            bodyFields:  snapshotBodyFields(op),
            responses:   Object.keys(op.responses || {}).sort(),
        };
    }

    const driftHashCache = new WeakMap();

    function hashEndpoint(op) {
        if (driftHashCache.has(op)) return driftHashCache.get(op);
        const hash = djb2(JSON.stringify(snapshotEndpoint(op)));
        driftHashCache.set(op, hash);
        return hash;
    }

    function extractEndpoints() {
        const out = [];
        for (const [path, pathObj] of Object.entries(driftSpec.paths || {})) {
            for (const method of DRIFT_METHODS) {
                const op = pathObj[method];
                if (!op) continue;
                out.push({
                    key:    method.toUpperCase() + ' ' + path,
                    method: method.toUpperCase(),
                    path,
                    tag:    (op.tags && op.tags[0]) || 'Other',
                    op,
                });
            }
        }
        return out;
    }

    function getDriftStatus(key, op) {
        const stored = driftState.endpoints[key];
        if (!stored || stored.status === 'todo') return 'todo';
        if (stored.specHash !== hashEndpoint(op)) return 'updated';
        return 'done';
    }

    function computeDiff(key, op) {
        const stored = driftState.endpoints[key];
        if (!stored || !stored.snapshot) return [{ sign: ' ', text: 'spec changed', breaking: false }];

        const prev    = stored.snapshot;
        const curr    = snapshotEndpoint(op);
        const changes = [];

        const prevP = new Map((prev.params || []).map(function (p) { return [p.in + ':' + p.name, p]; }));
        const currP = new Map((curr.params || []).map(function (p) { return [p.in + ':' + p.name, p]; }));
        for (const [k, p] of currP) {
            if (!prevP.has(k)) changes.push({ sign: '+', text: 'param: ' + k.split(':')[1], breaking: !!p.required });
        }
        for (const [k] of prevP) {
            if (!currP.has(k)) changes.push({ sign: '-', text: 'param: ' + k.split(':')[1], breaking: true });
        }
        for (const [k, currParam] of currP) {
            const prevParam = prevP.get(k);
            if (prevParam && !!prevParam.required !== !!currParam.required) {
                changes.push({
                    sign: currParam.required ? '+' : '-',
                    text: 'param: ' + k.split(':')[1] + ' is now ' + (currParam.required ? 'required' : 'optional'),
                    breaking: !!currParam.required,
                });
            }
        }

        if (prev.hasBody !== curr.hasBody) {
            changes.push(curr.hasBody
                ? { sign: '+', text: 'request body added', breaking: true }
                : { sign: '-', text: 'request body removed', breaking: false });
        }

        const prevB = new Map((prev.bodyFields || []).map(function (f) { return [f.name, f]; }));
        const currB = new Map((curr.bodyFields || []).map(function (f) { return [f.name, f]; }));
        for (const [k, f] of currB) {
            if (!prevB.has(k)) changes.push({ sign: '+', text: 'body field: ' + k, breaking: !!f.required });
        }
        for (const [k] of prevB) {
            if (!currB.has(k)) changes.push({ sign: '-', text: 'body field: ' + k, breaking: true });
        }
        for (const [k, currField] of currB) {
            const prevField = prevB.get(k);
            if (!prevField) continue;
            if (!!prevField.required !== !!currField.required) {
                changes.push({
                    sign: currField.required ? '+' : '-',
                    text: 'body field: ' + k + ' is now ' + (currField.required ? 'required' : 'optional'),
                    breaking: !!currField.required,
                });
            }
            if (prevField.type !== currField.type) {
                changes.push({ sign: ' ', text: 'body field: ' + k + ' type changed (' + prevField.type + ' → ' + currField.type + ')', breaking: true });
            }
        }

        const prevR = new Set(prev.responses || []);
        const currR = new Set(curr.responses || []);
        for (const r of currR) if (!prevR.has(r)) changes.push({ sign: '+', text: 'response ' + r, breaking: false });
        for (const r of prevR) if (!currR.has(r)) changes.push({ sign: '-', text: 'response ' + r, breaking: true });

        if (prev.summary !== curr.summary) changes.push({ sign: ' ', text: 'summary changed', breaking: false });
        if (prev.description !== curr.description) changes.push({ sign: ' ', text: 'description changed', breaking: false });

        return changes.length ? changes : [{ sign: ' ', text: 'spec changed', breaking: false }];
    }

    function markDriftDone(key, op) {
        driftState.endpoints[key] = {
            status:    'done',
            specHash:  hashEndpoint(op),
            snapshot:  snapshotEndpoint(op),
            markedAt:  new Date().toISOString(),
        };
        saveDriftState();
    }

    function markDriftTodo(key, op) {
        driftState.endpoints[key] = {
            status:   'todo',
            specHash: hashEndpoint(op),
            snapshot: snapshotEndpoint(op),
        };
        saveDriftState();
    }

    function pruneStaleDriftState() {
        const currentKeys = new Set(extractEndpoints().map(function (e) { return e.key; }));
        let changed = false;
        let removedChanged = false;
        const existingRemoved = new Set(driftRemoved.map(function (r) { return r.key; }));
        for (const key of Object.keys(driftState.endpoints)) {
            if (!currentKeys.has(key)) {
                if (!existingRemoved.has(key)) {
                    driftRemoved.push({ key: key, status: driftState.endpoints[key].status });
                    removedChanged = true;
                }
                delete driftState.endpoints[key];
                changed = true;
            }
        }
        if (changed) saveDriftState();
        if (removedChanged) saveDriftRemoved();
    }

    function createEl(tag, className) {
        const e = document.createElement(tag);
        if (className) e.className = className;
        return e;
    }

    function createTxtEl(tag, text, className) {
        const e = createEl(tag, className);
        e.textContent = text;
        return e;
    }

    function buildPathEl(path, className) {
        const e = createEl('span', className);
        const parts = path.split('/');
        parts.forEach(function (part, i) {
            if (i > 0) e.appendChild(document.createTextNode('/'));
            e.appendChild(document.createTextNode(part));
            if (i < parts.length - 1) e.appendChild(document.createElement('wbr'));
        });
        return e;
    }

    function renderDriftPill(endpoints) {
        let done = 0, upd = 0;
        for (const e of endpoints) {
            const s = getDriftStatus(e.key, e.op);
            if (s === 'done') done++;
            else if (s === 'updated') upd++;
        }

        const countEl = document.getElementById('drift-pill-count');
        if (countEl) countEl.textContent = done + '/' + endpoints.length;

        const updEl = document.getElementById('drift-pill-upd');
        if (updEl) {
            if (upd) {
                updEl.textContent = upd + ' updated';
                updEl.hidden = false;
            } else {
                updEl.hidden = true;
            }
        }
    }

    function renderDriftFilterCounts(endpoints) {
        let todo = 0, done = 0, updated = 0;
        for (const e of endpoints) {
            const s = getDriftStatus(e.key, e.op);
            if (s === 'todo') todo++;
            else if (s === 'done') done++;
            else if (s === 'updated') updated++;
        }
        const counts = { all: endpoints.length, todo: todo, done: done, updated: updated };
        for (const f of ['all', 'todo', 'done', 'updated']) {
            const cEl = document.getElementById('drift-tf-count-' + f);
            if (cEl) cEl.textContent = counts[f];
        }
    }

    function buildDriftRow(ep) {
        const status = getDriftStatus(ep.key, ep.op);
        const isDone = status === 'done';
        const isUpd  = status === 'updated';

        const row  = createEl('div', 'drift-row' + (isUpd ? ' updated' : ''));
        const main = createEl('div', 'drift-main');

        const cb = createEl('input', 'drift-cb');
        cb.type          = 'checkbox';
        cb.checked       = isDone;
        cb.title         = isUpd ? 'Spec changed since marked done — click to re-mark as done' : isDone ? 'Mark as todo' : 'Mark as done';

        const cbHit = createEl('span', 'drift-cb-hit');
        cbHit.appendChild(cb);
        cbHit.addEventListener('click', function (e) {
            e.stopPropagation();
            e.preventDefault();
            if (isUpd)       { driftExpandedDiffs.delete(ep.key); markDriftDone(ep.key, ep.op); }
            else if (isDone) { markDriftTodo(ep.key, ep.op); }
            else             { markDriftDone(ep.key, ep.op); }
            renderDrift();
        });

        const cssVar  = DRIFT_METHOD_VAR[ep.method] || '--drift-text-2';
        const badge   = createTxtEl('span', ep.method, 'drift-badge');
        badge.style.cssText = 'color:var(' + cssVar + ');background:color-mix(in srgb,var(' + cssVar + ') 12%,transparent)';

        const path = buildPathEl(ep.path, 'drift-path' + (isDone ? ' done' : ''));
        path.title = ep.path;

        main.appendChild(cbHit);
        main.appendChild(badge);
        main.appendChild(path);

        if (isUpd) {
            const diff = computeDiff(ep.key, ep.op);
            const isBreaking = diff.some(function (c) { return c.breaking; });
            if (isBreaking || driftFilter !== 'updated') {
                const updBadge = createTxtEl('span', isBreaking ? '⚠ breaking' : '⚠ updated', 'drift-upd-badge' + (isBreaking ? ' breaking' : ''));
                main.appendChild(updBadge);
            }

            const isExpanded = driftExpandedDiffs.has(ep.key);
            const chevron = createTxtEl('button', isExpanded ? '▾' : '▸', 'drift-chevron');
            chevron.setAttribute('aria-label', isExpanded ? 'Collapse diff' : 'Expand diff');
            chevron.addEventListener('click', function (e) {
                e.stopPropagation();
                if (isExpanded) driftExpandedDiffs.delete(ep.key);
                else driftExpandedDiffs.add(ep.key);
                renderDrift();
            });
            main.appendChild(chevron);
        }

        row.appendChild(main);

        if (isUpd && driftExpandedDiffs.has(ep.key)) {
            const diffEl = createEl('div', 'drift-diff');
            for (const c of computeDiff(ep.key, ep.op)) {
                const cls = c.breaking ? 'drift-diff-rem' : c.sign === '+' ? 'drift-diff-add' : c.sign === '-' ? 'drift-diff-rem' : 'drift-diff-neu';
                diffEl.appendChild(createTxtEl('div', c.sign + ' ' + c.text, cls));
            }
            const ackBtn = createTxtEl('button', '✓ Mark as done', 'drift-ack');
            ackBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                driftExpandedDiffs.delete(ep.key);
                markDriftDone(ep.key, ep.op);
                renderDrift();
            });
            row.appendChild(diffEl);
            row.appendChild(ackBtn);
        }

        return row;
    }

    function renderDriftList(endpoints) {
        const list = document.getElementById('drift-list');
        if (!list) return;
        const resetScroll = driftFilter !== driftLastFilter;
        driftLastFilter = driftFilter;
        const prevScrollTop = list.scrollTop;
        list.textContent = '';

        const visible = endpoints.filter(function (e) {
            const s = getDriftStatus(e.key, e.op);
            return driftFilter === 'all'     ? true
                 : driftFilter === 'updated' ? s === 'updated'
                 : s === driftFilter;
        });

        if (!visible.length) {
            const emptyText = endpoints.length === 0
                ? 'No endpoints found in the OpenAPI spec.'
                : 'No endpoints match this filter.';
            list.appendChild(createTxtEl('div', emptyText, 'drift-empty'));
            list.scrollTop = resetScroll ? 0 : prevScrollTop;
            return;
        }

        if (driftFilter === 'updated') {
            const bulkBtn = createTxtEl('button', 'Mark all ' + visible.length + ' reviewed', 'drift-bulk-ack');
            bulkBtn.addEventListener('click', function () {
                for (const e of visible) { driftExpandedDiffs.delete(e.key); markDriftDone(e.key, e.op); }
                renderDrift();
            });
            list.appendChild(bulkBtn);
        } else if (driftFilter !== 'done') {
            const bulkBtn = createTxtEl('button', 'Mark all ' + visible.length + ' as done', 'drift-bulk-ack');
            bulkBtn.addEventListener('click', function () {
                for (const e of visible) { driftExpandedDiffs.delete(e.key); markDriftDone(e.key, e.op); }
                renderDrift();
            });
            const unbulkBtn = createTxtEl('button', 'Mark all as todo', 'drift-bulk-ack drift-bulk-todo');
            unbulkBtn.addEventListener('click', function () {
                for (const e of visible) { markDriftTodo(e.key, e.op); }
                renderDrift();
            });
            const bulkRow = createEl('div', 'drift-bulk-row');
            bulkRow.appendChild(bulkBtn);
            bulkRow.appendChild(unbulkBtn);
            list.appendChild(bulkRow);
        } else {
            const unbulkBtn = createTxtEl('button', 'Mark all ' + visible.length + ' as todo', 'drift-bulk-ack drift-bulk-todo');
            unbulkBtn.addEventListener('click', function () {
                for (const e of visible) { markDriftTodo(e.key, e.op); }
                renderDrift();
            });
            list.appendChild(unbulkBtn);
        }

        const groups = new Map();
        for (const e of visible) {
            if (!groups.has(e.tag)) groups.set(e.tag, []);
            groups.get(e.tag).push(e);
        }

        for (const eps of groups.values()) {
            eps.sort(function (a, b) {
                const aUpd = getDriftStatus(a.key, a.op) === 'updated' ? 0 : 1;
                const bUpd = getDriftStatus(b.key, b.op) === 'updated' ? 0 : 1;
                return aUpd - bUpd;
            });
        }

        const tagOrder = new Map((driftSpec.tags || []).map(function (t, i) { return [t.name, i]; }));
        const orderedTags = Array.from(groups.keys()).sort(function (a, b) {
            const aIdx = tagOrder.has(a) ? tagOrder.get(a) : Infinity;
            const bIdx = tagOrder.has(b) ? tagOrder.get(b) : Infinity;
            return aIdx - bIdx;
        });

        const frag = document.createDocumentFragment();
        for (const tag of orderedTags) {
            frag.appendChild(createTxtEl('div', tag, 'drift-tag'));
            for (const ep of groups.get(tag)) frag.appendChild(buildDriftRow(ep));
        }
        list.appendChild(frag);
        list.scrollTop = resetScroll ? 0 : prevScrollTop;
    }

    function renderDrift() {
        if (!driftSpec) return;
        const endpoints = extractEndpoints();
        renderDriftPill(endpoints);
        renderDriftFilterCounts(endpoints);
        renderDriftRemovedBanner();
        if (driftPanelOpen) renderDriftList(endpoints);
    }

    function buildDriftPanel() {
        const panel = createEl('div');
        panel.id = 'drift-panel';

        const header = createEl('div');
        header.id = 'drift-header';
        const title = createTxtEl('span', 'Drift');
        title.id = 'drift-header-title';
        const headerActions = createEl('div');
        headerActions.id = 'drift-header-actions';
        const refreshBtn = createTxtEl('button', '⟳');
        refreshBtn.id = 'drift-refresh';
        refreshBtn.setAttribute('aria-label', 'Refresh spec');
        refreshBtn.title = 'Refresh spec';
        refreshBtn.addEventListener('click', function () { refreshDriftSpec(); });
        const closeBtn = createTxtEl('button', '×');
        closeBtn.id = 'drift-close';
        closeBtn.setAttribute('aria-label', 'Close Drift');
        closeBtn.addEventListener('click', function () { toggleDriftPanel(false); });
        headerActions.appendChild(refreshBtn);
        headerActions.appendChild(closeBtn);
        header.appendChild(title);
        header.appendChild(headerActions);

        const filtersEl = createEl('div');
        filtersEl.id = 'drift-filters';
        for (const f of ['all', 'todo', 'done', 'updated']) {
            const label = f.charAt(0).toUpperCase() + f.slice(1);
            const btn = createEl('button', 'drift-tf' + (f === 'all' ? ' active' : ''));
            btn.appendChild(document.createTextNode(label + ' '));
            const count = createTxtEl('span', '0', 'drift-tf-count');
            count.id = 'drift-tf-count-' + f;
            btn.appendChild(count);
            btn.addEventListener('click', function () {
                driftFilter = f;
                filtersEl.querySelectorAll('.drift-tf').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                renderDrift();
            });
            filtersEl.appendChild(btn);
        }

        const removedEl = createEl('div');
        removedEl.id = 'drift-removed';

        const listEl = createEl('div');
        listEl.id = 'drift-list';

        const footer = createEl('div');
        footer.id = 'drift-footer';
        const footerLink = createEl('a');
        footerLink.href = '';
        footerLink.target = '_blank';
        footerLink.rel = 'noopener noreferrer';
        footerLink.textContent = 'Drift · by Barreq';
        footer.appendChild(footerLink);

        panel.appendChild(header);
        panel.appendChild(filtersEl);
        panel.appendChild(removedEl);
        panel.appendChild(listEl);
        panel.appendChild(footer);
        document.body.appendChild(panel);
    }

    function renderDriftRemovedBanner() {
        const removedEl = document.getElementById('drift-removed');
        if (!removedEl) return;
        removedEl.textContent = '';
        if (!driftRemoved.length) return;

        const banner = createEl('div', 'drift-removed-banner');
        const title = createTxtEl('div', driftRemoved.length + ' tracked endpoint' + (driftRemoved.length === 1 ? '' : 's') + ' removed from the API:', 'drift-removed-title');
        banner.appendChild(title);

        for (const r of driftRemoved) {
            const row = createEl('div', 'drift-removed-row');
            row.appendChild(createTxtEl('span', r.key, 'drift-removed-key'));
            const dismissBtn = createTxtEl('button', '×', 'drift-removed-dismiss');
            dismissBtn.setAttribute('aria-label', 'Dismiss ' + r.key);
            dismissBtn.addEventListener('click', function () {
                driftRemoved = driftRemoved.filter(function (x) { return x.key !== r.key; });
                saveDriftRemoved();
                renderDriftRemovedBanner();
            });
            row.appendChild(dismissBtn);
            banner.appendChild(row);
        }

        const clearAllBtn = createTxtEl('button', 'Dismiss all', 'drift-removed-clear');
        clearAllBtn.addEventListener('click', function () {
            driftRemoved = [];
            saveDriftRemoved();
            renderDriftRemovedBanner();
        });
        banner.appendChild(clearAllBtn);

        removedEl.appendChild(banner);
    }

    async function fetchDriftSpec() {
        if (driftSpecFetched) return;
        driftSpecFetched = true;
        try {
            const res = await fetch('/docs/api.json', { credentials: 'same-origin' });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            driftSpec = await res.json();
            pruneStaleDriftState();
            const label = document.getElementById('drift-pill-label');
            const retry = document.getElementById('drift-pill-retry');
            if (label) label.textContent = 'Drift';
            if (retry) retry.hidden = true;
            renderDrift();
        } catch (e) {
            console.error('[drift]', e);
            const label = document.getElementById('drift-pill-label');
            const retry = document.getElementById('drift-pill-retry');
            if (label) label.textContent = 'Drift — error';
            if (retry) retry.hidden = false;
        }
    }

    function refreshDriftSpec() {
        driftSpecFetched = false;
        fetchDriftSpec();
    }

    function toggleDriftPanel(open) {
        driftPanelOpen = (open === undefined) ? !driftPanelOpen : open;
        const panel = document.getElementById('drift-panel');
        const pill  = document.getElementById('drift-pill');
        if (!panel || !pill) return;

        pill.style.visibility = driftPanelOpen ? 'hidden' : 'visible';
        if (driftPanelOpen) {
            panel.classList.add('open');
            const listEl = document.getElementById('drift-list');
            if (listEl && !driftSpec) {
                listEl.textContent = '';
                listEl.appendChild(createTxtEl('div', 'Loading endpoints…', 'drift-empty'));
            }
            if (!driftSpecFetched) fetchDriftSpec(); else renderDrift();
        } else {
            panel.classList.remove('open');
        }
    }

    function initDriftWidget() {
        loadDriftState();
        loadDriftRemoved();

        const pill = createEl('div');
        pill.id = 'drift-pill';
        pill.setAttribute('role', 'button');
        pill.setAttribute('tabindex', '0');
        pill.setAttribute('aria-label', 'Open Drift');
        pill.addEventListener('click', function () { toggleDriftPanel(); });
        pill.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleDriftPanel(); }
        });
        const pillLabel  = createTxtEl('span', 'Drift');
        pillLabel.id = 'drift-pill-label';
        const pillCount  = createTxtEl('span', '—');
        pillCount.id = 'drift-pill-count';
        const pillUpd    = createTxtEl('span', '');
        pillUpd.id = 'drift-pill-upd';
        pillUpd.hidden = true;
        const pillRetry  = createTxtEl('button', '⟳ retry');
        pillRetry.id = 'drift-pill-retry';
        pillRetry.hidden = true;
        pillRetry.addEventListener('click', function (e) {
            e.stopPropagation();
            refreshDriftSpec();
        });
        pill.appendChild(pillLabel);
        pill.appendChild(pillCount);
        pill.appendChild(pillUpd);
        pill.appendChild(pillRetry);
        document.body.appendChild(pill);

        buildDriftPanel();

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && driftPanelOpen) toggleDriftPanel(false);
        });

        setTimeout(fetchDriftSpec, 300);
    }

    // ==========================================================================
    // Initializer
    // ==========================================================================
    function init() {
        const spec = window.globalSpec;
        const docs = document.getElementById('docs');
        if (docs && spec) {
            docs.apiDescriptionDocument = spec;
        }

        initApiBaseUrlBar();
        if (spec) {
            initSingleApiRunner(spec);
        }
        relocateCustomContainer();
        setupSystemThemeTracker();
        initDriftWidget();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }


    // Initialize Theme
    function initTheme() {
        const savedTheme = localStorage.getItem('scramble_theme') || window.systemTheme || 'light';
        applyTheme(savedTheme);
    }

    window.initTheme = initTheme;

    // Toggle Function
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        applyTheme(newTheme);
    }

    window.toggleTheme = toggleTheme;

    // Apply Theme to DOM and Save
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        document.documentElement.style.colorScheme = theme;
        localStorage.setItem('scramble_theme', theme);

        const icon = document.getElementById('theme-toggle-icon');
        const text = document.getElementById('theme-toggle-text');

        if (icon && text) {
            if (theme === 'dark') {
                icon.textContent = '☀️';
                text.textContent = 'Light Mode';
            } else {
                icon.textContent = '🌙';
                text.textContent = 'Dark Mode';
            }
        }
    }

})();
// Run on Load
initTheme();
