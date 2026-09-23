/**
 * Eclassify Standalone Photo Editor Plugin
 * Enterprise-grade client-side image editor with crop, rotate, fine straighten, flip, filters, text overlay, stickers, and undo/redo.
 * Zero external library dependencies - uses native HTML5 Canvas API.
 */
(function (window, document) {
  'use strict';

  var PhotoEditor = {
    modal: null,
    canvas: null,
    ctx: null,
    originalImage: null,
    currentImage: null,
    history: [],
    historyIndex: -1,
    maxHistory: 20,

    overlayLayer: null,
    selectedOverlayId: null,
    zoomLevel: 1.0,

    // Current State
    state: {
      rotation: 0,
      fineRotation: 0,
      flipH: 1,
      flipV: 1,
      brightness: 0,
      contrast: 0,
      saturation: 0,
      filter: 'none',
      texts: [], // legacy fallback
      stickers: [], // legacy fallback
      overlays: [], // { id, type, text, xRatio, yRatio, size, fontFamily, bold, italic, underline, uppercase, shadow, align, color, bgColor }
      cropActive: false,
      cropAspect: null, // null = free, 1 = 1:1, 1.3333 = 4:3, 1.7777 = 16:9, etc.
      cropRect: { x: 0, y: 0, w: 0, h: 0 }
    },

    activeTab: 'crop',
    currentFile: null,
    onSaveCallback: null,
    onCancelCallback: null,
    _listenersAttached: false,

    init: function () {
      if (document.getElementById('pe-modal-overlay')) {
        this.modal = document.getElementById('pe-modal-overlay');
        this.canvas = document.getElementById('pe-main-canvas');
        this.ctx = this.canvas.getContext('2d');
        this.overlayLayer = document.getElementById('pe-overlay-layer');
        if (!this._listenersAttached) {
          this.bindEvents();
        }
        return;
      }
      this.createModalHtml();
      this.modal = document.getElementById('pe-modal-overlay');
      this.canvas = document.getElementById('pe-main-canvas');
      this.ctx = this.canvas.getContext('2d');
      this.overlayLayer = document.getElementById('pe-overlay-layer');
      this.bindEvents();
    },

    createModalHtml: function () {
      var html = `
      <div id="pe-modal-overlay" class="pe-modal-overlay" role="dialog" aria-modal="true">
        <div class="pe-modal-container">
          <!-- Header -->
          <div class="pe-header">
            <div class="pe-title">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              <span>Image Editor</span>
            </div>
            <div class="pe-header-actions">
              <button type="button" class="pe-btn pe-btn-secondary" id="pe-btn-cancel">Cancel</button>
              <button type="button" class="pe-btn pe-btn-primary" id="pe-btn-save">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Apply & Save
              </button>
              <button type="button" class="pe-btn-close" id="pe-btn-close" aria-label="Close">&times;</button>
            </div>
          </div>

          <!-- Body -->
          <div class="pe-body">
            <!-- Canvas Viewport -->
            <div class="pe-canvas-viewport" id="pe-canvas-viewport">
              <div class="pe-canvas-wrapper" id="pe-canvas-wrapper">
                <canvas id="pe-main-canvas"></canvas>
                <div id="pe-overlay-layer" class="pe-overlay-layer"></div>
                <div id="pe-crop-box" class="pe-crop-box">
                  <div class="pe-crop-handle pe-handle-tl" data-handle="tl"></div>
                  <div class="pe-crop-handle pe-handle-tr" data-handle="tr"></div>
                  <div class="pe-crop-handle pe-handle-bl" data-handle="bl"></div>
                  <div class="pe-crop-handle pe-handle-br" data-handle="br"></div>
                </div>
              </div>

              <!-- Viewport Zoom HUD Controls -->
              <div class="pe-zoom-hud">
                <button type="button" class="pe-zoom-btn" id="pe-btn-zoom-out" title="Zoom Out">&minus;</button>
                <button type="button" class="pe-zoom-btn pe-zoom-val" id="pe-btn-zoom-fit" title="Fit to Screen">100%</button>
                <button type="button" class="pe-zoom-btn" id="pe-btn-zoom-in" title="Zoom In">&plus;</button>
              </div>
            </div>

            <!-- Tools Sidebar -->
            <div class="pe-sidebar">
              <div class="pe-tabs">
                <button type="button" class="pe-tab-btn active" data-tab="crop">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6.13 1L6 16a2 2 0 0 0 2 2h15"/><path d="M1 6.13L16 6a2 2 0 0 1 2 2v15"/></svg>
                  <span>Crop</span>
                </button>
                <button type="button" class="pe-tab-btn" data-tab="rotate">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                  <span>Rotate</span>
                </button>
                <button type="button" class="pe-tab-btn" data-tab="adjust">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/></svg>
                  <span>Adjust</span>
                </button>
                <button type="button" class="pe-tab-btn" data-tab="filter">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                  <span>Filter</span>
                </button>
                <button type="button" class="pe-tab-btn" data-tab="text">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
                  <span>Text</span>
                </button>
                <button type="button" class="pe-tab-btn" data-tab="stickers">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                  <span>Badges</span>
                </button>
              </div>

              <!-- Panels Scroll Container -->
              <div class="pe-panels-container">
                <!-- Crop Panel -->
                <div class="pe-panel active" id="pe-panel-crop">
                  <div class="pe-panel-title">Aspect Ratio</div>
                  <div class="pe-btn-grid">
                    <button type="button" class="pe-tool-btn active" data-crop-aspect="free">Free</button>
                    <button type="button" class="pe-tool-btn" data-crop-aspect="1">1 : 1 (Square)</button>
                    <button type="button" class="pe-tool-btn" data-crop-aspect="1.3333">4 : 3 (Standard)</button>
                    <button type="button" class="pe-tool-btn" data-crop-aspect="1.7777">16 : 9 (Wide)</button>
                    <button type="button" class="pe-tool-btn" data-crop-aspect="0.5625">9 : 16 (Story)</button>
                    <button type="button" class="pe-tool-btn" data-crop-aspect="1.5">3 : 2 (Classic)</button>
                  </div>
                  <button type="button" class="pe-btn pe-btn-primary" id="pe-btn-apply-crop" style="margin-top: 10px;">
                    Apply Crop
                  </button>
                </div>

                <!-- Rotate & Orientation Panel -->
                <div class="pe-panel" id="pe-panel-rotate">
                  <div class="pe-panel-title">Orientation</div>
                  <div class="pe-btn-grid">
                    <button type="button" class="pe-tool-btn" id="pe-btn-rot-ccw">&#x21BA; -90&deg;</button>
                    <button type="button" class="pe-tool-btn" id="pe-btn-rot-cw">&#x21BB; +90&deg;</button>
                    <button type="button" class="pe-tool-btn" id="pe-btn-flip-h">&#x21C4; Flip H</button>
                    <button type="button" class="pe-tool-btn" id="pe-btn-flip-v">&#x21C5; Flip V</button>
                  </div>
                  <div class="pe-control-group" style="margin-top: 12px;">
                    <label>Fine Straighten: <span id="pe-val-fine-rot">0&deg;</span></label>
                    <input type="range" class="pe-range-input" id="pe-range-fine-rot" min="-45" max="45" value="0">
                  </div>
                  <button type="button" class="pe-btn pe-btn-secondary" id="pe-btn-reset-fine-rot" style="margin-top: 6px; font-size: 0.8rem; align-self: flex-start;">
                    Reset Straighten
                  </button>
                </div>

                <!-- Adjust Panel -->
                <div class="pe-panel" id="pe-panel-adjust">
                  <div class="pe-panel-title">Enhancements</div>
                  <div class="pe-control-group">
                    <label>Brightness: <span id="pe-val-bright">0</span></label>
                    <input type="range" class="pe-range-input" id="pe-range-bright" min="-100" max="100" value="0">
                  </div>
                  <div class="pe-control-group">
                    <label>Contrast: <span id="pe-val-contrast">0</span></label>
                    <input type="range" class="pe-range-input" id="pe-range-contrast" min="-100" max="100" value="0">
                  </div>
                  <div class="pe-control-group">
                    <label>Saturation: <span id="pe-val-sat">0</span></label>
                    <input type="range" class="pe-range-input" id="pe-range-sat" min="-100" max="100" value="0">
                  </div>
                  <button type="button" class="pe-btn pe-btn-secondary" id="pe-btn-reset-adjust" style="margin-top: 8px; font-size: 0.8rem; align-self: flex-start;">
                    Reset Adjustments
                  </button>
                </div>

                <!-- Filter Panel -->
                <div class="pe-panel" id="pe-panel-filter">
                  <div class="pe-panel-title">Presets</div>
                  <div class="pe-filter-grid">
                    <div class="pe-filter-card active" data-filter="none">Original</div>
                    <div class="pe-filter-card" data-filter="grayscale">Grayscale</div>
                    <div class="pe-filter-card" data-filter="sepia">Sepia</div>
                    <div class="pe-filter-card" data-filter="warm">Warm</div>
                    <div class="pe-filter-card" data-filter="cool">Cool</div>
                    <div class="pe-filter-card" data-filter="vintage">Vintage</div>
                    <div class="pe-filter-card" data-filter="invert">Invert</div>
                    <div class="pe-filter-card" data-filter="contrast">Vibrant</div>
                  </div>
                </div>

                <!-- Text Panel -->
                <div class="pe-panel" id="pe-panel-text">
                  <div class="pe-panel-title">Add & Edit Text</div>
                  <input type="text" class="pe-text-input" id="pe-text-string" placeholder="Type text here...">
                  
                  <div class="pe-control-group">
                    <label>Font Family</label>
                    <select class="pe-select-input" id="pe-select-font-family">
                      <option value="sans-serif">Modern Sans-Serif</option>
                      <option value="serif">Classic Serif</option>
                      <option value="monospace">Monospace</option>
                      <option value="Impact, sans-serif">Impact / Bold</option>
                      <option value="'Comic Sans MS', cursive, sans-serif">Handwriting</option>
                    </select>
                  </div>

                  <div class="pe-control-group">
                    <label>Font Size: <span id="pe-val-font-size">28px</span></label>
                    <div class="pe-stepper-wrap">
                      <button type="button" class="pe-stepper-btn" id="pe-btn-font-dec" title="Decrease font size">&minus;</button>
                      <input type="range" class="pe-range-input" id="pe-range-font-size" min="12" max="96" value="28">
                      <button type="button" class="pe-stepper-btn" id="pe-btn-font-inc" title="Increase font size">&plus;</button>
                    </div>
                    <div class="pe-pill-group" style="margin-top: 4px;">
                      <button type="button" class="pe-pill-btn" data-quick-size="16">16px</button>
                      <button type="button" class="pe-pill-btn" data-quick-size="24">24px</button>
                      <button type="button" class="pe-pill-btn active" data-quick-size="28">28px</button>
                      <button type="button" class="pe-pill-btn" data-quick-size="36">36px</button>
                      <button type="button" class="pe-pill-btn" data-quick-size="48">48px</button>
                    </div>
                  </div>

                  <div class="pe-control-group">
                    <label>Alignment & Formatting</label>
                    <div class="pe-btn-trio">
                      <button type="button" class="pe-tool-btn" id="pe-btn-text-align-left" title="Align Left">Left</button>
                      <button type="button" class="pe-tool-btn active" id="pe-btn-text-align-center" title="Align Center">Center</button>
                      <button type="button" class="pe-tool-btn" id="pe-btn-text-align-right" title="Align Right">Right</button>
                    </div>
                    <div class="pe-btn-quad" style="margin-top: 6px;">
                      <button type="button" class="pe-tool-btn" id="pe-btn-text-bold" title="Bold"><b>B</b></button>
                      <button type="button" class="pe-tool-btn" id="pe-btn-text-italic" title="Italic"><i>I</i></button>
                      <button type="button" class="pe-tool-btn" id="pe-btn-text-underline" title="Underline"><u>U</u></button>
                      <button type="button" class="pe-tool-btn" id="pe-btn-text-case" title="Uppercase">aA</button>
                    </div>
                    <button type="button" class="pe-tool-btn" id="pe-btn-text-shadow" style="margin-top: 6px; width: 100%;">
                      Outline Shadow (High Contrast)
                    </button>
                  </div>

                  <div class="pe-control-group">
                    <label>Text Color</label>
                    <div class="pe-color-row">
                      <input type="color" class="pe-color-input" id="pe-color-text" value="#ffffff" title="Custom color picker">
                      <div class="pe-color-swatches" id="pe-text-swatches">
                        <span class="pe-swatch-dot" data-color="#FFFFFF" style="background: #FFFFFF;" title="White"></span>
                        <span class="pe-swatch-dot" data-color="#000000" style="background: #000000;" title="Black"></span>
                        <span class="pe-swatch-dot" data-color="#EF4444" style="background: #EF4444;" title="Red"></span>
                        <span class="pe-swatch-dot" data-color="#F59E0B" style="background: #F59E0B;" title="Amber"></span>
                        <span class="pe-swatch-dot" data-color="#10B981" style="background: #10B981;" title="Green"></span>
                        <span class="pe-swatch-dot" data-color="#3B82F6" style="background: #3B82F6;" title="Blue"></span>
                        <span class="pe-swatch-dot" data-color="#8B5CF6" style="background: #8B5CF6;" title="Purple"></span>
                      </div>
                    </div>
                  </div>

                  <div class="pe-control-group">
                    <label>Background Color</label>
                    <div class="pe-color-row">
                      <input type="color" class="pe-color-input" id="pe-color-bg" value="#000000" title="Custom bg picker">
                      <div class="pe-color-swatches" id="pe-bg-swatches">
                        <span class="pe-swatch-dot" data-bg="transparent" style="background: linear-gradient(45deg, #ccc 25%, transparent 25%), linear-gradient(-45deg, #ccc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #ccc 75%), linear-gradient(-45deg, transparent 75%, #ccc 75%); background-size: 8px 8px; background-position: 0 0, 0 4px, 4px -4px, -4px 0px;" title="None"></span>
                        <span class="pe-swatch-dot" data-bg="#000000" style="background: #000000;" title="Black"></span>
                        <span class="pe-swatch-dot" data-bg="#FFFFFF" style="background: #FFFFFF;" title="White"></span>
                        <span class="pe-swatch-dot" data-bg="#1E293B" style="background: #1E293B;" title="Slate"></span>
                        <span class="pe-swatch-dot" data-bg="#EF4444" style="background: #EF4444;" title="Red"></span>
                        <span class="pe-swatch-dot" data-bg="#2563EB" style="background: #2563EB;" title="Blue"></span>
                      </div>
                    </div>
                  </div>

                  <button type="button" class="pe-btn pe-btn-primary" id="pe-btn-add-text" style="margin-top: 8px;">
                    + Add Text to Image
                  </button>

                  <!-- Selected Overlay Action Controls -->
                  <div id="pe-overlay-actions-wrap" style="margin-top: 12px; border-top: 1px solid var(--pe-border-light); padding-top: 10px;">
                    <div class="pe-panel-title" style="margin-bottom: 6px;">Overlay Controls</div>
                    <div class="pe-btn-grid">
                      <button type="button" class="pe-tool-btn" id="pe-btn-overlay-dup" title="Duplicate selected item">Duplicate</button>
                      <button type="button" class="pe-tool-btn" id="pe-btn-overlay-center" title="Center on canvas">Center</button>
                      <button type="button" class="pe-tool-btn" id="pe-btn-overlay-del" style="color: #ef4444;" title="Delete selected item">Delete</button>
                      <button type="button" class="pe-tool-btn" id="pe-btn-overlay-clear" title="Clear all overlays">Clear All</button>
                    </div>
                  </div>
                </div>

                <!-- Badges / Stickers Panel -->
                <div class="pe-panel" id="pe-panel-stickers">
                  <div class="pe-panel-title">Click to Add Badge</div>
                  <div class="pe-badge-grid">
                    <div class="pe-badge-item" style="background: #e11d48;" data-badge="SALE">SALE</div>
                    <div class="pe-badge-item" style="background: #ea580c;" data-badge="HOT DEAL">HOT DEAL</div>
                    <div class="pe-badge-item" style="background: #16a34a;" data-badge="NEW">NEW</div>
                    <div class="pe-badge-item" style="background: #2563eb;" data-badge="VERIFIED">&#x2713; VERIFIED</div>
                    <div class="pe-badge-item" style="background: #7c3aed;" data-badge="FEATURED">&#x2605; FEATURED</div>
                    <div class="pe-badge-item" style="background: #d97706;" data-badge="BEST OFFER">BEST OFFER</div>
                    <div class="pe-badge-item" style="background: #059669;" data-badge="TOP RATED">TOP RATED</div>
                    <div class="pe-badge-item" style="background: #dc2626;" data-badge="URGENT">URGENT</div>
                  </div>

                  <div class="pe-panel-title" style="margin-top: 14px;">Create Custom Badge</div>
                  <div class="pe-control-group">
                    <input type="text" class="pe-text-input" id="pe-custom-badge-text" placeholder="Custom badge (e.g. 50% OFF)">
                    <div class="pe-color-row" style="margin-top: 6px;">
                      <label style="font-size: 0.8rem;">Color:</label>
                      <input type="color" class="pe-color-input" id="pe-custom-badge-color" value="#e11d48">
                      <button type="button" class="pe-btn pe-btn-secondary" id="pe-btn-add-custom-badge" style="flex: 1; justify-content: center; font-size: 0.82rem;">
                        + Add Badge
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="pe-footer">
            <div class="pe-history-actions">
              <button type="button" class="pe-tool-btn" id="pe-btn-undo" title="Undo (Ctrl+Z)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg> Undo
              </button>
              <button type="button" class="pe-tool-btn" id="pe-btn-redo" title="Redo (Ctrl+Y)">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3L21 13"/></svg> Redo
              </button>
              <button type="button" class="pe-tool-btn" id="pe-btn-reset" title="Reset All Changes">Reset</button>
            </div>
            <div style="font-size: 0.8rem; color: var(--pe-text-muted-light);" id="pe-dim-info">
              Ready
            </div>
          </div>
        </div>
      </div>
      `;
      var div = document.createElement('div');
      div.innerHTML = html;
      document.body.appendChild(div.firstElementChild);
    },

    bindEvents: function () {
      var self = this;
      this._listenersAttached = true;

      // Close / Cancel
      document.getElementById('pe-btn-close').addEventListener('click', function () { self.close(); });
      document.getElementById('pe-btn-cancel').addEventListener('click', function () { self.close(); });

      // Save
      document.getElementById('pe-btn-save').addEventListener('click', function () { self.save(); });

      // Tab switching
      var tabBtns = document.querySelectorAll('.pe-tab-btn');
      tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var targetTab = this.getAttribute('data-tab');
          tabBtns.forEach(function (b) { b.classList.remove('active'); });
          this.classList.add('active');

          document.querySelectorAll('.pe-panel').forEach(function (p) { p.classList.remove('active'); });
          var targetPanel = document.getElementById('pe-panel-' + targetTab);
          if (targetPanel) targetPanel.classList.add('active');

          self.activeTab = targetTab;
          if (targetTab === 'crop') {
            self.showCropBox();
          } else {
            self.hideCropBox();
          }
        });
      });

      // Crop Aspect buttons
      document.querySelectorAll('[data-crop-aspect]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          document.querySelectorAll('[data-crop-aspect]').forEach(function (b) { b.classList.remove('active'); });
          this.classList.add('active');
          var aspect = this.getAttribute('data-crop-aspect');
          self.setCropAspect(aspect === 'free' ? null : parseFloat(aspect));
        });
      });

      // Apply crop
      document.getElementById('pe-btn-apply-crop').addEventListener('click', function () {
        self.applyCrop();
      });

      // Rotate / Flip
      document.getElementById('pe-btn-rot-cw').addEventListener('click', function () { self.rotate(90); });
      document.getElementById('pe-btn-rot-ccw').addEventListener('click', function () { self.rotate(-90); });
      document.getElementById('pe-btn-flip-h').addEventListener('click', function () { self.flip('h'); });
      document.getElementById('pe-btn-flip-v').addEventListener('click', function () { self.flip('v'); });

      // Fine Straighten
      var fineRotInput = document.getElementById('pe-range-fine-rot');
      var fineRotVal = document.getElementById('pe-val-fine-rot');
      if (fineRotInput) {
        fineRotInput.addEventListener('input', function () {
          var deg = parseInt(this.value, 10) || 0;
          fineRotVal.innerText = deg + '\u00B0';
          self.state.fineRotation = deg;
          self.render();
        });
        fineRotInput.addEventListener('change', function () {
          self.pushHistory();
        });
      }
      var resetFineRotBtn = document.getElementById('pe-btn-reset-fine-rot');
      if (resetFineRotBtn) {
        resetFineRotBtn.addEventListener('click', function () {
          if (fineRotInput) fineRotInput.value = 0;
          if (fineRotVal) fineRotVal.innerText = '0\u00B0';
          self.state.fineRotation = 0;
          self.render();
          self.pushHistory();
        });
      }

      // Sliders (Adjust)
      var bright = document.getElementById('pe-range-bright');
      var contrast = document.getElementById('pe-range-contrast');
      var sat = document.getElementById('pe-range-sat');

      bright.addEventListener('input', function () {
        document.getElementById('pe-val-bright').innerText = this.value;
        self.state.brightness = parseInt(this.value, 10);
        self.render();
      });
      bright.addEventListener('change', function () { self.pushHistory(); });

      contrast.addEventListener('input', function () {
        document.getElementById('pe-val-contrast').innerText = this.value;
        self.state.contrast = parseInt(this.value, 10);
        self.render();
      });
      contrast.addEventListener('change', function () { self.pushHistory(); });

      sat.addEventListener('input', function () {
        document.getElementById('pe-val-sat').innerText = this.value;
        self.state.saturation = parseInt(this.value, 10);
        self.render();
      });
      sat.addEventListener('change', function () { self.pushHistory(); });

      var resetAdjustBtn = document.getElementById('pe-btn-reset-adjust');
      if (resetAdjustBtn) {
        resetAdjustBtn.addEventListener('click', function () {
          bright.value = 0;
          document.getElementById('pe-val-bright').innerText = '0';
          contrast.value = 0;
          document.getElementById('pe-val-contrast').innerText = '0';
          sat.value = 0;
          document.getElementById('pe-val-sat').innerText = '0';

          self.state.brightness = 0;
          self.state.contrast = 0;
          self.state.saturation = 0;
          self.render();
          self.pushHistory();
        });
      }

      // Filters
      document.querySelectorAll('[data-filter]').forEach(function (card) {
        card.addEventListener('click', function () {
          document.querySelectorAll('[data-filter]').forEach(function (c) { c.classList.remove('active'); });
          this.classList.add('active');
          self.state.filter = this.getAttribute('data-filter');
          self.render();
          self.pushHistory();
        });
      });

      // Viewport click: deselect overlay when clicking background
      var viewport = document.getElementById('pe-canvas-viewport');
      if (viewport) {
        viewport.addEventListener('pointerdown', function (e) {
          if (!e.target.closest('.pe-overlay-item') && !e.target.closest('.pe-crop-box') && !e.target.closest('.pe-zoom-hud')) {
            self.deselectOverlay();
          }
        });

        // Mousewheel zoom in viewport
        viewport.addEventListener('wheel', function (e) {
          if (e.ctrlKey || e.metaKey) {
            e.preventDefault();
            if (e.deltaY < 0) {
              self.setZoom(Math.min(3.0, self.zoomLevel + 0.1));
            } else {
              self.setZoom(Math.max(0.3, self.zoomLevel - 0.1));
            }
          }
        }, { passive: false });
      }

      // Zoom HUD buttons
      var zoomInBtn = document.getElementById('pe-btn-zoom-in');
      var zoomOutBtn = document.getElementById('pe-btn-zoom-out');
      var zoomFitBtn = document.getElementById('pe-btn-zoom-fit');

      if (zoomInBtn) {
        zoomInBtn.addEventListener('click', function () {
          self.setZoom(Math.min(3.0, self.zoomLevel + 0.15));
        });
      }
      if (zoomOutBtn) {
        zoomOutBtn.addEventListener('click', function () {
          self.setZoom(Math.max(0.3, self.zoomLevel - 0.15));
        });
      }
      if (zoomFitBtn) {
        zoomFitBtn.addEventListener('click', function () {
          self.setZoom(1.0);
        });
      }

      // Text tools & live updates
      var textInput = document.getElementById('pe-text-string');
      textInput.addEventListener('input', function () {
        if (self.selectedOverlayId) {
          var overlay = self.state.overlays.find(function (o) { return o.id === self.selectedOverlayId; });
          if (overlay && overlay.type === 'text') {
            overlay.text = this.value;
            var el = document.querySelector('.pe-overlay-item[data-id="' + overlay.id + '"] .pe-overlay-text-content');
            if (el) el.textContent = this.value;
          }
        }
      });
      textInput.addEventListener('change', function () {
        if (self.selectedOverlayId) self.pushHistory();
      });

      // Font Family
      var fontFamilySelect = document.getElementById('pe-select-font-family');
      if (fontFamilySelect) {
        fontFamilySelect.addEventListener('change', function () {
          var fam = this.value;
          if (self.selectedOverlayId) {
            var overlay = self.state.overlays.find(function (o) { return o.id === self.selectedOverlayId; });
            if (overlay && overlay.type === 'text') {
              overlay.fontFamily = fam;
              var el = document.querySelector('.pe-overlay-item[data-id="' + overlay.id + '"] .pe-overlay-text-content');
              if (el) el.style.fontFamily = fam;
              self.pushHistory();
            }
          }
        });
      }

      // Font size slider & steppers
      var fontSizeRange = document.getElementById('pe-range-font-size');
      var fontDecBtn = document.getElementById('pe-btn-font-dec');
      var fontIncBtn = document.getElementById('pe-btn-font-inc');

      var updateFontSize = function (sz) {
        sz = Math.max(12, Math.min(96, sz));
        fontSizeRange.value = sz;
        document.getElementById('pe-val-font-size').innerText = sz + 'px';

        // Update pills
        document.querySelectorAll('[data-quick-size]').forEach(function (pill) {
          if (parseInt(pill.getAttribute('data-quick-size'), 10) === sz) {
            pill.classList.add('active');
          } else {
            pill.classList.remove('active');
          }
        });

        if (self.selectedOverlayId) {
          var overlay = self.state.overlays.find(function (o) { return o.id === self.selectedOverlayId; });
          if (overlay && overlay.type === 'text') {
            overlay.size = sz;
            var el = document.querySelector('.pe-overlay-item[data-id="' + overlay.id + '"] .pe-overlay-text-content');
            if (el) el.style.fontSize = sz + 'px';
          }
        }
      };

      fontSizeRange.addEventListener('input', function () {
        updateFontSize(parseInt(this.value, 10));
      });
      fontSizeRange.addEventListener('change', function () { self.pushHistory(); });

      if (fontDecBtn) {
        fontDecBtn.addEventListener('click', function () {
          var current = parseInt(fontSizeRange.value, 10) || 28;
          updateFontSize(current - 2);
          self.pushHistory();
        });
      }
      if (fontIncBtn) {
        fontIncBtn.addEventListener('click', function () {
          var current = parseInt(fontSizeRange.value, 10) || 28;
          updateFontSize(current + 2);
          self.pushHistory();
        });
      }

      // Quick size pills
      document.querySelectorAll('[data-quick-size]').forEach(function (pill) {
        pill.addEventListener('click', function () {
          var sz = parseInt(this.getAttribute('data-quick-size'), 10);
          updateFontSize(sz);
          self.pushHistory();
        });
      });

      // Alignment Trio
      var alignLeftBtn = document.getElementById('pe-btn-text-align-left');
      var alignCenterBtn = document.getElementById('pe-btn-text-align-center');
      var alignRightBtn = document.getElementById('pe-btn-text-align-right');
      var setAlignment = function (align) {
        [alignLeftBtn, alignCenterBtn, alignRightBtn].forEach(function (b) { if (b) b.classList.remove('active'); });
        if (align === 'left' && alignLeftBtn) alignLeftBtn.classList.add('active');
        if (align === 'center' && alignCenterBtn) alignCenterBtn.classList.add('active');
        if (align === 'right' && alignRightBtn) alignRightBtn.classList.add('active');

        if (self.selectedOverlayId) {
          var overlay = self.state.overlays.find(function (o) { return o.id === self.selectedOverlayId; });
          if (overlay && overlay.type === 'text') {
            overlay.align = align;
            var el = document.querySelector('.pe-overlay-item[data-id="' + overlay.id + '"] .pe-overlay-text-content');
            if (el) el.style.textAlign = align;
            self.pushHistory();
          }
        }
      };

      if (alignLeftBtn) alignLeftBtn.addEventListener('click', function () { setAlignment('left'); });
      if (alignCenterBtn) alignCenterBtn.addEventListener('click', function () { setAlignment('center'); });
      if (alignRightBtn) alignRightBtn.addEventListener('click', function () { setAlignment('right'); });

      // Formatting: Bold, Italic, Underline, Uppercase, Shadow
      var boldBtn = document.getElementById('pe-btn-text-bold');
      boldBtn.addEventListener('click', function () {
        this.classList.toggle('active');
        if (self.selectedOverlayId) {
          var overlay = self.state.overlays.find(function (o) { return o.id === self.selectedOverlayId; });
          if (overlay && overlay.type === 'text') {
            overlay.bold = this.classList.contains('active');
            var el = document.querySelector('.pe-overlay-item[data-id="' + overlay.id + '"] .pe-overlay-text-content');
            if (el) el.style.fontWeight = overlay.bold ? 'bold' : 'normal';
            self.pushHistory();
          }
        }
      });

      var italicBtn = document.getElementById('pe-btn-text-italic');
      italicBtn.addEventListener('click', function () {
        this.classList.toggle('active');
        if (self.selectedOverlayId) {
          var overlay = self.state.overlays.find(function (o) { return o.id === self.selectedOverlayId; });
          if (overlay && overlay.type === 'text') {
            overlay.italic = this.classList.contains('active');
            var el = document.querySelector('.pe-overlay-item[data-id="' + overlay.id + '"] .pe-overlay-text-content');
            if (el) el.style.fontStyle = overlay.italic ? 'italic' : 'normal';
            self.pushHistory();
          }
        }
      });

      var underlineBtn = document.getElementById('pe-btn-text-underline');
      if (underlineBtn) {
        underlineBtn.addEventListener('click', function () {
          this.classList.toggle('active');
          if (self.selectedOverlayId) {
            var overlay = self.state.overlays.find(function (o) { return o.id === self.selectedOverlayId; });
            if (overlay && overlay.type === 'text') {
              overlay.underline = this.classList.contains('active');
              var el = document.querySelector('.pe-overlay-item[data-id="' + overlay.id + '"] .pe-overlay-text-content');
              if (el) el.style.textDecoration = overlay.underline ? 'underline' : 'none';
              self.pushHistory();
            }
          }
        });
      }

      var caseBtn = document.getElementById('pe-btn-text-case');
      if (caseBtn) {
        caseBtn.addEventListener('click', function () {
          this.classList.toggle('active');
          if (self.selectedOverlayId) {
            var overlay = self.state.overlays.find(function (o) { return o.id === self.selectedOverlayId; });
            if (overlay && overlay.type === 'text') {
              overlay.uppercase = this.classList.contains('active');
              var el = document.querySelector('.pe-overlay-item[data-id="' + overlay.id + '"] .pe-overlay-text-content');
              if (el) el.style.textTransform = overlay.uppercase ? 'uppercase' : 'none';
              self.pushHistory();
            }
          }
        });
      }

      var shadowBtn = document.getElementById('pe-btn-text-shadow');
      if (shadowBtn) {
        shadowBtn.addEventListener('click', function () {
          this.classList.toggle('active');
          if (self.selectedOverlayId) {
            var overlay = self.state.overlays.find(function (o) { return o.id === self.selectedOverlayId; });
            if (overlay && overlay.type === 'text') {
              overlay.shadow = this.classList.contains('active');
              var el = document.querySelector('.pe-overlay-item[data-id="' + overlay.id + '"] .pe-overlay-text-content');
              if (el) {
                if (overlay.shadow) el.classList.add('has-shadow');
                else el.classList.remove('has-shadow');
              }
              self.pushHistory();
            }
          }
        });
      }

      // Text color & swatches
      var textColorInput = document.getElementById('pe-color-text');
      var setTextColor = function (col) {
        textColorInput.value = col;
        if (self.selectedOverlayId) {
          var overlay = self.state.overlays.find(function (o) { return o.id === self.selectedOverlayId; });
          if (overlay) {
            overlay.color = col;
            var el = document.querySelector('.pe-overlay-item[data-id="' + overlay.id + '"] .pe-overlay-text-content') ||
                     document.querySelector('.pe-overlay-item[data-id="' + overlay.id + '"] .pe-overlay-badge-content');
            if (el) el.style.color = col;
          }
        }
      };
      textColorInput.addEventListener('input', function () { setTextColor(this.value); });
      textColorInput.addEventListener('change', function () { self.pushHistory(); });

      document.querySelectorAll('#pe-text-swatches .pe-swatch-dot').forEach(function (dot) {
        dot.addEventListener('click', function () {
          var col = this.getAttribute('data-color');
          setTextColor(col);
          self.pushHistory();
        });
      });

      // Bg color & swatches
      var bgColorInput = document.getElementById('pe-color-bg');
      var setBgColor = function (bg) {
        if (bg !== 'transparent') bgColorInput.value = bg;
        if (self.selectedOverlayId) {
          var overlay = self.state.overlays.find(function (o) { return o.id === self.selectedOverlayId; });
          if (overlay) {
            overlay.bgColor = bg;
            var el = document.querySelector('.pe-overlay-item[data-id="' + overlay.id + '"] .pe-overlay-text-content') ||
                     document.querySelector('.pe-overlay-item[data-id="' + overlay.id + '"] .pe-overlay-badge-content');
            if (el) el.style.backgroundColor = bg;
          }
        }
      };
      bgColorInput.addEventListener('input', function () { setBgColor(this.value); });
      bgColorInput.addEventListener('change', function () { self.pushHistory(); });

      document.querySelectorAll('#pe-bg-swatches .pe-swatch-dot').forEach(function (dot) {
        dot.addEventListener('click', function () {
          var bg = this.getAttribute('data-bg');
          setBgColor(bg);
          self.pushHistory();
        });
      });

      // Add Text Button
      document.getElementById('pe-btn-add-text').addEventListener('click', function () {
        var text = textInput.value.trim();
        if (!text) {
          textInput.focus();
          return;
        }

        var fontSize = parseInt(fontSizeRange.value, 10) || 28;
        var fontFamily = fontFamilySelect ? fontFamilySelect.value : 'sans-serif';
        var color = textColorInput.value || '#ffffff';
        var bgColor = bgColorInput.value || 'transparent';
        var bold = boldBtn.classList.contains('active');
        var italic = italicBtn.classList.contains('active');
        var underline = underlineBtn ? underlineBtn.classList.contains('active') : false;
        var uppercase = caseBtn ? caseBtn.classList.contains('active') : false;
        var shadow = shadowBtn ? shadowBtn.classList.contains('active') : false;
        var align = 'center';
        if (alignLeftBtn && alignLeftBtn.classList.contains('active')) align = 'left';
        if (alignRightBtn && alignRightBtn.classList.contains('active')) align = 'right';

        var newOverlay = {
          id: 'text_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
          type: 'text',
          text: text,
          xRatio: 0.5,
          yRatio: 0.5,
          size: fontSize,
          fontFamily: fontFamily,
          bold: bold,
          italic: italic,
          underline: underline,
          uppercase: uppercase,
          shadow: shadow,
          align: align,
          color: color,
          bgColor: bgColor
        };

        if (!self.state.overlays) self.state.overlays = [];
        self.state.overlays.push(newOverlay);
        self.renderOverlaysDOM();
        self.selectOverlay(newOverlay.id);
        self.pushHistory();
      });

      // Overlay Quick Action Buttons (Duplicate, Center, Delete, Clear All)
      var dupBtn = document.getElementById('pe-btn-overlay-dup');
      if (dupBtn) {
        dupBtn.addEventListener('click', function () { self.duplicateSelectedOverlay(); });
      }
      var centerBtn = document.getElementById('pe-btn-overlay-center');
      if (centerBtn) {
        centerBtn.addEventListener('click', function () { self.centerSelectedOverlay(); });
      }
      var delBtn = document.getElementById('pe-btn-overlay-del');
      if (delBtn) {
        delBtn.addEventListener('click', function () {
          if (self.selectedOverlayId) self.deleteOverlay(self.selectedOverlayId);
        });
      }
      var clearBtn = document.getElementById('pe-btn-overlay-clear');
      if (clearBtn) {
        clearBtn.addEventListener('click', function () { self.clearAllOverlays(); });
      }

      // Badges / Stickers Panel
      document.querySelectorAll('.pe-badge-item').forEach(function (item) {
        item.addEventListener('click', function () {
          var badgeText = this.getAttribute('data-badge');
          var bg = window.getComputedStyle(this).backgroundColor || '#e11d48';

          var newOverlay = {
            id: 'badge_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
            type: 'badge',
            text: badgeText,
            xRatio: 0.5,
            yRatio: 0.35,
            size: 20,
            bold: true,
            italic: false,
            color: '#FFFFFF',
            bgColor: bg
          };

          if (!self.state.overlays) self.state.overlays = [];
          self.state.overlays.push(newOverlay);
          self.renderOverlaysDOM();
          self.selectOverlay(newOverlay.id);
          self.pushHistory();
        });
      });

      // Custom Badge Creator
      var addCustomBadgeBtn = document.getElementById('pe-btn-add-custom-badge');
      var customBadgeText = document.getElementById('pe-custom-badge-text');
      var customBadgeColor = document.getElementById('pe-custom-badge-color');
      if (addCustomBadgeBtn && customBadgeText) {
        addCustomBadgeBtn.addEventListener('click', function () {
          var text = customBadgeText.value.trim();
          if (!text) {
            customBadgeText.focus();
            return;
          }
          var col = customBadgeColor ? customBadgeColor.value : '#e11d48';

          var newOverlay = {
            id: 'badge_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
            type: 'badge',
            text: text,
            xRatio: 0.5,
            yRatio: 0.4,
            size: 20,
            bold: true,
            italic: false,
            color: '#FFFFFF',
            bgColor: col
          };

          if (!self.state.overlays) self.state.overlays = [];
          self.state.overlays.push(newOverlay);
          self.renderOverlaysDOM();
          self.selectOverlay(newOverlay.id);
          self.pushHistory();
          customBadgeText.value = '';
        });
      }

      // History controls
      document.getElementById('pe-btn-undo').addEventListener('click', function () { self.undo(); });
      document.getElementById('pe-btn-redo').addEventListener('click', function () { self.redo(); });
      document.getElementById('pe-btn-reset').addEventListener('click', function () { self.reset(); });

      // Keyboard Shortcuts
      window.addEventListener('keydown', function (e) {
        if (!self.modal || !self.modal.classList.contains('pe-open')) return;

        var activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
        var isInputActive = activeTag === 'input' || activeTag === 'textarea' || activeTag === 'select';

        // Delete key
        if ((e.key === 'Delete' || e.key === 'Backspace') && !isInputActive) {
          if (self.selectedOverlayId) {
            e.preventDefault();
            self.deleteOverlay(self.selectedOverlayId);
          }
        }

        // Ctrl+Z: Undo
        if ((e.ctrlKey || e.metaKey) && (e.key === 'z' || e.key === 'Z') && !e.shiftKey) {
          if (!isInputActive) {
            e.preventDefault();
            self.undo();
          }
        }

        // Ctrl+Y / Ctrl+Shift+Z: Redo
        if ((e.ctrlKey || e.metaKey) && ((e.key === 'y' || e.key === 'Y') || (e.shiftKey && (e.key === 'z' || e.key === 'Z')))) {
          if (!isInputActive) {
            e.preventDefault();
            self.redo();
          }
        }

        // Escape: Deselect overlay or close
        if (e.key === 'Escape') {
          if (self.selectedOverlayId) {
            self.deselectOverlay();
          } else {
            self.close();
          }
        }
      });

      // Window resize handler for dynamic screen fitting
      var resizeTimeout;
      window.addEventListener('resize', function () {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function () {
          if (self.modal && self.modal.classList.contains('pe-open')) {
            self.fitCanvasToViewport();
          }
        }, 80);
      });

      // Setup Crop Box dragging & resizing
      this.setupCropInteractions();
    },

    open: function (options) {
      this.init();
      options = options || {};
      this.onSaveCallback = options.onSave || null;
      this.onCancelCallback = options.onCancel || null;
      this.currentFile = options.file || null;

      var imageSrc = options.image || null;
      if (!imageSrc && this.currentFile) {
        imageSrc = URL.createObjectURL(this.currentFile);
      }

      if (!imageSrc) {
        console.error('PhotoEditor: No image source provided');
        return;
      }

      var self = this;
      var img = new Image();
      img.crossOrigin = 'anonymous';
      img.onload = function () {
        self.originalImage = img;
        self.currentImage = img;
        self.zoomLevel = 1.0;
        self.resetState();
        self.modal.classList.add('pe-open');
        self.render();
        self.pushHistory();
        if (self.activeTab === 'crop') {
          self.showCropBox();
        }
      };
      img.onerror = function () {
        console.error('PhotoEditor: Failed to load image from source:', imageSrc);
      };
      img.src = imageSrc;
    },

    close: function () {
      if (this.modal) {
        this.modal.classList.remove('pe-open');
      }
      this.hideCropBox();
      if (this.onCancelCallback) {
        this.onCancelCallback();
      }
    },

    resetState: function () {
      this.state = {
        rotation: 0,
        fineRotation: 0,
        flipH: 1,
        flipV: 1,
        brightness: 0,
        contrast: 0,
        saturation: 0,
        filter: 'none',
        texts: [],
        stickers: [],
        overlays: [],
        cropActive: false,
        cropAspect: null,
        cropRect: { x: 0, y: 0, w: 0, h: 0 }
      };
      this.selectedOverlayId = null;
      this.history = [];
      this.historyIndex = -1;

      // Reset controls UI
      var bright = document.getElementById('pe-range-bright');
      if (bright) bright.value = 0;
      var valBright = document.getElementById('pe-val-bright');
      if (valBright) valBright.innerText = '0';

      var contrast = document.getElementById('pe-range-contrast');
      if (contrast) contrast.value = 0;
      var valContrast = document.getElementById('pe-val-contrast');
      if (valContrast) valContrast.innerText = '0';

      var sat = document.getElementById('pe-range-sat');
      if (sat) sat.value = 0;
      var valSat = document.getElementById('pe-val-sat');
      if (valSat) valSat.innerText = '0';

      var fineRot = document.getElementById('pe-range-fine-rot');
      if (fineRot) fineRot.value = 0;
      var valFineRot = document.getElementById('pe-val-fine-rot');
      if (valFineRot) valFineRot.innerText = '0\u00B0';

      document.querySelectorAll('[data-filter]').forEach(function (c) {
        c.classList.remove('active');
        if (c.getAttribute('data-filter') === 'none') c.classList.add('active');
      });

      this.renderOverlaysDOM();
    },

    /**
     * Dynamically sizes the canvas wrapper to fit inside pe-canvas-viewport with studio-grade margins
     */
    fitCanvasToViewport: function () {
      var viewport = document.getElementById('pe-canvas-viewport');
      var wrapper = document.getElementById('pe-canvas-wrapper');
      var canvas = this.canvas;
      if (!viewport || !wrapper || !canvas || !this.currentImage) return;

      var pad = 36;
      var availW = Math.max(100, viewport.clientWidth - pad);
      var availH = Math.max(100, viewport.clientHeight - pad);

      var nativeW = canvas.width;
      var nativeH = canvas.height;
      if (!nativeW || !nativeH) return;

      var aspect = nativeW / nativeH;
      var displayW, displayH;

      if ((availW / availH) > aspect) {
        displayH = availH;
        displayW = displayH * aspect;
      } else {
        displayW = availW;
        displayH = displayW / aspect;
      }

      var currentZoom = this.zoomLevel || 1.0;
      displayW = Math.round(displayW * currentZoom);
      displayH = Math.round(displayH * currentZoom);

      wrapper.style.width = displayW + 'px';
      wrapper.style.height = displayH + 'px';

      // Update zoom HUD text
      var zoomValBtn = document.getElementById('pe-btn-zoom-fit');
      if (zoomValBtn) {
        zoomValBtn.innerText = Math.round(currentZoom * 100) + '%';
      }

      if (this.activeTab === 'crop') {
        this.updateCropBoxPosition();
      }
    },

    setZoom: function (zoom) {
      this.zoomLevel = Math.max(0.3, Math.min(3.0, zoom));
      this.fitCanvasToViewport();
    },

    render: function () {
      if (!this.currentImage) return;

      var img = this.currentImage;
      var rot = (this.state.rotation || 0) % 360;
      var fineRot = (this.state.fineRotation || 0);
      var totalRotDeg = rot + fineRot;

      var is90 = Math.abs(rot) === 90 || Math.abs(rot) === 270;
      var w = is90 ? img.height : img.width;
      var h = is90 ? img.width : img.height;

      this.canvas.width = w;
      this.canvas.height = h;

      var ctx = this.ctx;
      ctx.save();
      ctx.clearRect(0, 0, w, h);

      // Translate to center for rotation / flip
      ctx.translate(w / 2, h / 2);
      ctx.rotate((totalRotDeg * Math.PI) / 180);
      ctx.scale(this.state.flipH, this.state.flipV);

      // Draw base image
      ctx.drawImage(img, -img.width / 2, -img.height / 2);
      ctx.restore();

      // Apply CSS-like Filters & Adjustments via Pixel manipulation
      if (this.state.brightness !== 0 || this.state.contrast !== 0 || this.state.saturation !== 0 || this.state.filter !== 'none') {
        this.applyPixelFilters();
      }

      // Draw legacy text overlays (if any)
      if (this.state.texts && this.state.texts.length > 0) {
        for (var i = 0; i < this.state.texts.length; i++) {
          this.drawText(this.state.texts[i]);
        }
      }

      // Draw legacy stickers (if any)
      if (this.state.stickers && this.state.stickers.length > 0) {
        for (var j = 0; j < this.state.stickers.length; j++) {
          this.drawBadge(this.state.stickers[j]);
        }
      }

      // Dynamic studio-grade screen fitting
      this.fitCanvasToViewport();

      // Update dimension indicator
      var dimInfo = document.getElementById('pe-dim-info');
      if (dimInfo) {
        dimInfo.innerText = w + ' \u00D7 ' + h + ' px';
      }

      // Update crop overlay if active
      if (this.activeTab === 'crop') {
        this.updateCropBoxPosition();
      }

      // Render interactive draggable overlays in overlay DOM layer
      this.renderOverlaysDOM();
    },

    applyPixelFilters: function () {
      var imgData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
      var data = imgData.data;
      var bVal = this.state.brightness; // -100 to 100
      var cVal = this.state.contrast;   // -100 to 100
      var sVal = this.state.saturation; // -100 to 100
      var filter = this.state.filter;

      var contrastFactor = (259 * (cVal + 255)) / (255 * (259 - cVal));

      for (var i = 0; i < data.length; i += 4) {
        var r = data[i];
        var g = data[i + 1];
        var b = data[i + 2];

        // Brightness
        if (bVal !== 0) {
          r += bVal * 1.5;
          g += bVal * 1.5;
          b += bVal * 1.5;
        }

        // Contrast
        if (cVal !== 0) {
          r = contrastFactor * (r - 128) + 128;
          g = contrastFactor * (g - 128) + 128;
          b = contrastFactor * (b - 128) + 128;
        }

        // Preset Filters
        if (filter === 'grayscale') {
          var gray = 0.299 * r + 0.587 * g + 0.114 * b;
          r = g = b = gray;
        } else if (filter === 'sepia') {
          var sr = (r * 0.393) + (g * 0.769) + (b * 0.189);
          var sg = (r * 0.349) + (g * 0.686) + (b * 0.168);
          var sb = (r * 0.272) + (g * 0.534) + (b * 0.131);
          r = sr; g = sg; b = sb;
        } else if (filter === 'warm') {
          r += 25;
          b -= 15;
        } else if (filter === 'cool') {
          r -= 15;
          b += 25;
        } else if (filter === 'vintage') {
          var vgray = 0.299 * r + 0.587 * g + 0.114 * b;
          r = vgray + 40;
          g = vgray + 20;
          b = vgray - 10;
        } else if (filter === 'invert') {
          r = 255 - r;
          g = 255 - g;
          b = 255 - b;
        } else if (filter === 'contrast') {
          r = 1.3 * (r - 128) + 128;
          g = 1.3 * (g - 128) + 128;
          b = 1.3 * (b - 128) + 128;
        }

        // Saturation adjustment
        if (sVal !== 0) {
          var lum = 0.299 * r + 0.587 * g + 0.114 * b;
          var satRatio = 1 + (sVal / 100);
          r = lum + (r - lum) * satRatio;
          g = lum + (g - lum) * satRatio;
          b = lum + (b - lum) * satRatio;
        }

        data[i] = Math.min(255, Math.max(0, r));
        data[i + 1] = Math.min(255, Math.max(0, g));
        data[i + 2] = Math.min(255, Math.max(0, b));
      }

      this.ctx.putImageData(imgData, 0, 0);
    },

    drawText: function (t) {
      var ctx = this.ctx;
      ctx.save();
      var fontStyle = (t.italic ? 'italic ' : '') + (t.bold ? 'bold ' : '') + t.size + 'px ' + (t.fontFamily || 'sans-serif');
      ctx.font = fontStyle;
      ctx.textBaseline = 'middle';
      ctx.textAlign = t.align || 'center';

      var metrics = ctx.measureText(t.text);
      var textWidth = metrics.width;
      var textHeight = t.size * 1.25;

      // Draw background box if specified
      if (t.bgColor && t.bgColor !== 'transparent') {
        ctx.fillStyle = t.bgColor;
        var padX = 14;
        var padY = 8;
        var rx = t.x - (textWidth / 2) - padX;
        var ry = t.y - (textHeight / 2) - padY;
        var rw = textWidth + (padX * 2);
        var rh = textHeight + (padY * 2);

        this.roundRect(ctx, rx, ry, rw, rh, 8);
        ctx.fill();
      }

      ctx.fillStyle = t.color || '#FFFFFF';
      ctx.fillText(t.text, t.x, t.y);
      ctx.restore();
    },

    drawBadge: function (s) {
      var ctx = this.ctx;
      ctx.save();
      var fontSize = 20;
      ctx.font = 'bold ' + fontSize + 'px sans-serif';
      ctx.textBaseline = 'middle';
      ctx.textAlign = 'center';

      var metrics = ctx.measureText(s.badge);
      var textWidth = metrics.width;
      var padX = 16;
      var padY = 10;
      var rw = textWidth + (padX * 2);
      var rh = fontSize + (padY * 2);

      ctx.fillStyle = s.bg || '#E11D48';
      this.roundRect(ctx, s.x, s.y, rw, rh, 6);
      ctx.fill();

      ctx.fillStyle = s.color || '#FFFFFF';
      ctx.fillText(s.badge, s.x + (rw / 2), s.y + (rh / 2));
      ctx.restore();
    },

    roundRect: function (ctx, x, y, width, height, radius) {
      ctx.beginPath();
      ctx.moveTo(x + radius, y);
      ctx.lineTo(x + width - radius, y);
      ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
      ctx.lineTo(x + width, y + height - radius);
      ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
      ctx.lineTo(x + radius, y + height);
      ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
      ctx.lineTo(x, y + radius);
      ctx.quadraticCurveTo(x, y, x + radius, y);
      ctx.closePath();
    },

    rotate: function (deg) {
      if (this.state.overlays && this.state.overlays.length > 0) {
        for (var i = 0; i < this.state.overlays.length; i++) {
          var o = this.state.overlays[i];
          var oldX = o.xRatio;
          var oldY = o.yRatio;
          if (deg === 90 || deg === -270) {
            o.xRatio = Math.max(0.02, Math.min(0.98, 1 - oldY));
            o.yRatio = Math.max(0.02, Math.min(0.98, oldX));
          } else if (deg === -90 || deg === 270) {
            o.xRatio = Math.max(0.02, Math.min(0.98, oldY));
            o.yRatio = Math.max(0.02, Math.min(0.98, 1 - oldX));
          }
        }
      }
      this.state.rotation = (this.state.rotation + deg) % 360;
      this.render();
      this.pushHistory();
    },

    flip: function (axis) {
      if (axis === 'h') {
        this.state.flipH *= -1;
        if (this.state.overlays && this.state.overlays.length > 0) {
          for (var i = 0; i < this.state.overlays.length; i++) {
            this.state.overlays[i].xRatio = Math.max(0.02, Math.min(0.98, 1 - this.state.overlays[i].xRatio));
          }
        }
      }
      if (axis === 'v') {
        this.state.flipV *= -1;
        if (this.state.overlays && this.state.overlays.length > 0) {
          for (var j = 0; j < this.state.overlays.length; j++) {
            this.state.overlays[j].yRatio = Math.max(0.02, Math.min(0.98, 1 - this.state.overlays[j].yRatio));
          }
        }
      }
      this.render();
      this.pushHistory();
    },

    // Crop Implementation
    showCropBox: function () {
      var cropBox = document.getElementById('pe-crop-box');
      if (!cropBox) return;

      var rect = this.canvas.getBoundingClientRect();
      var w = rect.width * 0.8;
      var h = rect.height * 0.8;
      if (this.state.cropAspect) {
        h = w / this.state.cropAspect;
        if (h > rect.height * 0.9) {
          h = rect.height * 0.8;
          w = h * this.state.cropAspect;
        }
      }

      var x = (rect.width - w) / 2;
      var y = (rect.height - h) / 2;

      cropBox.style.left = x + 'px';
      cropBox.style.top = y + 'px';
      cropBox.style.width = w + 'px';
      cropBox.style.height = h + 'px';
      cropBox.style.display = 'block';
    },

    hideCropBox: function () {
      var cropBox = document.getElementById('pe-crop-box');
      if (cropBox) cropBox.style.display = 'none';
    },

    updateCropBoxPosition: function () {
      var cropBox = document.getElementById('pe-crop-box');
      if (!cropBox || cropBox.style.display === 'none') return;
      var rect = this.canvas.getBoundingClientRect();
      var curW = parseFloat(cropBox.style.width) || rect.width * 0.8;
      var curH = parseFloat(cropBox.style.height) || rect.height * 0.8;
      cropBox.style.width = Math.min(rect.width, curW) + 'px';
      cropBox.style.height = Math.min(rect.height, curH) + 'px';
    },

    setCropAspect: function (aspect) {
      this.state.cropAspect = aspect;
      this.showCropBox();
    },

    setupCropInteractions: function () {
      var cropBox = document.getElementById('pe-crop-box');
      var canvas = document.getElementById('pe-main-canvas');
      var isDragging = false;
      var isResizing = false;
      var activeHandle = null;
      var startX, startY, startLeft, startTop, startW, startH;

      var onMouseDown = function (e) {
        var handle = e.target.getAttribute('data-handle');
        if (handle) {
          isResizing = true;
          activeHandle = handle;
        } else if (e.target === cropBox) {
          isDragging = true;
        } else {
          return;
        }

        startX = e.clientX || (e.touches && e.touches[0].clientX);
        startY = e.clientY || (e.touches && e.touches[0].clientY);
        startLeft = parseFloat(cropBox.style.left) || 0;
        startTop = parseFloat(cropBox.style.top) || 0;
        startW = parseFloat(cropBox.style.width) || 0;
        startH = parseFloat(cropBox.style.height) || 0;

        e.preventDefault();
      };

      var onMouseMove = function (e) {
        if (!isDragging && !isResizing) return;

        var clientX = e.clientX || (e.touches && e.touches[0].clientX);
        var clientY = e.clientY || (e.touches && e.touches[0].clientY);
        var dx = clientX - startX;
        var dy = clientY - startY;

        var maxW = canvas.clientWidth;
        var maxH = canvas.clientHeight;

        if (isDragging) {
          var newLeft = Math.max(0, Math.min(maxW - startW, startLeft + dx));
          var newTop = Math.max(0, Math.min(maxH - startH, startTop + dy));
          cropBox.style.left = newLeft + 'px';
          cropBox.style.top = newTop + 'px';
        } else if (isResizing) {
          var newW = startW;
          var newH = startH;

          if (activeHandle === 'br') {
            newW = Math.max(40, Math.min(maxW - startLeft, startW + dx));
            newH = PhotoEditor.state.cropAspect ? newW / PhotoEditor.state.cropAspect : Math.max(40, Math.min(maxH - startTop, startH + dy));
          } else if (activeHandle === 'bl') {
            newW = Math.max(40, Math.min(startLeft + startW, startW - dx));
            cropBox.style.left = (startLeft + (startW - newW)) + 'px';
            newH = PhotoEditor.state.cropAspect ? newW / PhotoEditor.state.cropAspect : Math.max(40, Math.min(maxH - startTop, startH + dy));
          } else if (activeHandle === 'tr') {
            newW = Math.max(40, Math.min(maxW - startLeft, startW + dx));
            newH = PhotoEditor.state.cropAspect ? newW / PhotoEditor.state.cropAspect : Math.max(40, Math.min(startTop + startH, startH - dy));
            cropBox.style.top = (startTop + (startH - newH)) + 'px';
          } else if (activeHandle === 'tl') {
            newW = Math.max(40, Math.min(startLeft + startW, startW - dx));
            cropBox.style.left = (startLeft + (startW - newW)) + 'px';
            newH = PhotoEditor.state.cropAspect ? newW / PhotoEditor.state.cropAspect : Math.max(40, Math.min(startTop + startH, startH - dy));
            cropBox.style.top = (startTop + (startH - newH)) + 'px';
          }

          cropBox.style.width = newW + 'px';
          cropBox.style.height = newH + 'px';
        }
      };

      var onMouseUp = function () {
        isDragging = false;
        isResizing = false;
        activeHandle = null;
      };

      cropBox.addEventListener('mousedown', onMouseDown);
      cropBox.addEventListener('touchstart', onMouseDown);
      window.addEventListener('mousemove', onMouseMove);
      window.addEventListener('touchmove', onMouseMove);
      window.addEventListener('mouseup', onMouseUp);
      window.addEventListener('touchend', onMouseUp);
    },

    applyCrop: function () {
      var cropBox = document.getElementById('pe-crop-box');
      if (!cropBox || cropBox.style.display === 'none') return;

      this.deselectOverlay();
      this.bakeOverlaysToCanvas();

      var canvas = this.canvas;
      var scaleX = canvas.width / canvas.clientWidth;
      var scaleY = canvas.height / canvas.clientHeight;

      var cropX = (parseFloat(cropBox.style.left) || 0) * scaleX;
      var cropY = (parseFloat(cropBox.style.top) || 0) * scaleY;
      var cropW = (parseFloat(cropBox.style.width) || 0) * scaleX;
      var cropH = (parseFloat(cropBox.style.height) || 0) * scaleY;

      if (cropW <= 10 || cropH <= 10) return;

      var tempCanvas = document.createElement('canvas');
      tempCanvas.width = cropW;
      tempCanvas.height = cropH;
      var tempCtx = tempCanvas.getContext('2d');

      tempCtx.drawImage(canvas, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH);

      var croppedImg = new Image();
      var self = this;
      croppedImg.onload = function () {
        self.currentImage = croppedImg;
        self.state.rotation = 0;
        self.state.fineRotation = 0;
        self.state.flipH = 1;
        self.state.flipV = 1;
        self.state.texts = [];
        self.state.stickers = [];
        self.state.overlays = [];
        self.render();
        self.renderOverlaysDOM();
        self.pushHistory();
        self.showCropBox();
      };
      croppedImg.src = tempCanvas.toDataURL('image/jpeg', 0.95);
    },

    // History (Undo / Redo / Reset)
    pushHistory: function () {
      if (this.historyIndex < this.history.length - 1) {
        this.history = this.history.slice(0, this.historyIndex + 1);
      }

      var snapshot = {
        dataUrl: this.canvas.toDataURL('image/jpeg', 0.92),
        state: JSON.parse(JSON.stringify(this.state))
      };

      this.history.push(snapshot);
      if (this.history.length > this.maxHistory) {
        this.history.shift();
      } else {
        this.historyIndex++;
      }
    },

    undo: function () {
      if (this.historyIndex > 0) {
        this.historyIndex--;
        this.restoreHistory(this.history[this.historyIndex]);
      }
    },

    redo: function () {
      if (this.historyIndex < this.history.length - 1) {
        this.historyIndex++;
        this.restoreHistory(this.history[this.historyIndex]);
      }
    },

    restoreHistory: function (snapshot) {
      if (!snapshot) return;
      var self = this;
      var img = new Image();
      img.onload = function () {
        self.currentImage = img;
        self.state = JSON.parse(JSON.stringify(snapshot.state));
        self.render();
        self.renderOverlaysDOM();
      };
      img.src = snapshot.dataUrl;
    },

    reset: function () {
      this.currentImage = this.originalImage;
      this.zoomLevel = 1.0;
      this.resetState();
      this.render();
      this.pushHistory();
    },

    // Save and export
    save: function () {
      var self = this;
      this.deselectOverlay();
      this.bakeOverlaysToCanvas();

      this.canvas.toBlob(function (blob) {
        var originalName = (self.currentFile && self.currentFile.name) ? self.currentFile.name : 'edited_image.jpg';
        var filename = 'edited_' + originalName.replace(/\.[^/.]+$/, "") + '.jpg';
        var file = new File([blob], filename, { type: 'image/jpeg' });
        var dataUrl = self.canvas.toDataURL('image/jpeg', 0.92);

        if (self.onSaveCallback) {
          self.onSaveCallback(blob, file, dataUrl);
        }

        self.close();
      }, 'image/jpeg', 0.92);
    },

    // Overlays DOM & Interaction System
    renderOverlaysDOM: function () {
      var layer = document.getElementById('pe-overlay-layer');
      if (!layer) return;

      layer.innerHTML = '';
      var self = this;

      if (!this.state.overlays || !this.state.overlays.length) return;

      this.state.overlays.forEach(function (overlay) {
        var itemEl = document.createElement('div');
        itemEl.className = 'pe-overlay-item' + (self.selectedOverlayId === overlay.id ? ' selected' : '');
        itemEl.setAttribute('data-id', overlay.id);
        itemEl.style.left = (overlay.xRatio * 100) + '%';
        itemEl.style.top = (overlay.yRatio * 100) + '%';

        // Delete button
        var delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.className = 'pe-overlay-del-btn';
        delBtn.innerHTML = '&times;';
        delBtn.title = 'Delete overlay';
        delBtn.addEventListener('click', function (e) {
          e.stopPropagation();
          self.deleteOverlay(overlay.id);
        });
        itemEl.appendChild(delBtn);

        // Overlay Content
        if (overlay.type === 'text') {
          var textEl = document.createElement('div');
          textEl.className = 'pe-overlay-text-content' + (overlay.shadow ? ' has-shadow' : '');
          textEl.style.fontSize = (overlay.size || 28) + 'px';
          textEl.style.color = overlay.color || '#ffffff';
          textEl.style.fontFamily = overlay.fontFamily || 'sans-serif';
          textEl.style.textAlign = overlay.align || 'center';
          if (overlay.bgColor && overlay.bgColor !== 'transparent') {
            textEl.style.backgroundColor = overlay.bgColor;
          } else {
            textEl.style.backgroundColor = 'transparent';
          }
          if (overlay.bold) textEl.style.fontWeight = 'bold';
          if (overlay.italic) textEl.style.fontStyle = 'italic';
          if (overlay.underline) textEl.style.textDecoration = 'underline';
          if (overlay.uppercase) textEl.style.textTransform = 'uppercase';

          textEl.textContent = overlay.text;
          itemEl.appendChild(textEl);
        } else if (overlay.type === 'badge') {
          var badgeEl = document.createElement('div');
          badgeEl.className = 'pe-overlay-badge-content';
          badgeEl.style.backgroundColor = overlay.bgColor || '#e11d48';
          badgeEl.style.color = overlay.color || '#ffffff';
          badgeEl.textContent = overlay.text;
          itemEl.appendChild(badgeEl);
        }

        // Bounding box outline (visible when selected)
        var boxEl = document.createElement('div');
        boxEl.className = 'pe-overlay-box';
        itemEl.appendChild(boxEl);

        // 8 Interactive Resize Handles (Corner & Edge)
        var handleTypes = ['tl', 'tr', 'br', 'bl', 'tc', 'bc', 'ml', 'mr'];
        handleTypes.forEach(function (hType) {
          var hEl = document.createElement('div');
          hEl.className = 'pe-resize-handle pe-handle-' + hType;
          hEl.setAttribute('data-resize-handle', hType);
          itemEl.appendChild(hEl);
        });

        // Click / Double-click interactions
        itemEl.addEventListener('click', function (e) {
          if (!e.target.closest('.pe-overlay-del-btn') && !e.target.closest('.pe-resize-handle')) {
            self.selectOverlay(overlay.id);
          }
        });

        itemEl.addEventListener('dblclick', function (e) {
          if (!e.target.closest('.pe-overlay-del-btn') && !e.target.closest('.pe-resize-handle')) {
            self.selectOverlay(overlay.id);
            if (overlay.type === 'text') {
              var textTabBtn = document.querySelector('.pe-tab-btn[data-tab="text"]');
              if (textTabBtn) textTabBtn.click();
              var textInput = document.getElementById('pe-text-string');
              if (textInput) {
                textInput.focus();
                textInput.select();
              }
            }
          }
        });

        self.setupOverlayItemDrag(itemEl, overlay);
        self.setupOverlayItemResize(itemEl, overlay);
        layer.appendChild(itemEl);
      });
    },

    setupOverlayItemDrag: function (itemEl, overlay) {
      var self = this;
      var isDragging = false;
      var startX, startY;
      var startXRatio, startYRatio;
      var overlayLayer = document.getElementById('pe-overlay-layer');

      var onStart = function (e) {
        if (e.target.closest('.pe-overlay-del-btn') || e.target.closest('.pe-resize-handle')) return;
        isDragging = true;
        self.selectOverlay(overlay.id);

        var p = e.touches ? e.touches[0] : e;
        startX = p.clientX;
        startY = p.clientY;
        startXRatio = overlay.xRatio;
        startYRatio = overlay.yRatio;

        e.stopPropagation();
        e.preventDefault();
      };

      var onMove = function (e) {
        if (!isDragging) return;
        var p = e.touches ? e.touches[0] : e;
        var dx = p.clientX - startX;
        var dy = p.clientY - startY;

        var rect = overlayLayer ? overlayLayer.getBoundingClientRect() : null;
        if (!rect || rect.width <= 0 || rect.height <= 0) return;

        var newXRatio = Math.max(0.01, Math.min(0.99, startXRatio + (dx / rect.width)));
        var newYRatio = Math.max(0.01, Math.min(0.99, startYRatio + (dy / rect.height)));

        overlay.xRatio = newXRatio;
        overlay.yRatio = newYRatio;

        itemEl.style.left = (newXRatio * 100) + '%';
        itemEl.style.top = (newYRatio * 100) + '%';
      };

      var onEnd = function () {
        if (!isDragging) return;
        isDragging = false;
        self.pushHistory();
      };

      itemEl.addEventListener('mousedown', onStart);
      itemEl.addEventListener('touchstart', onStart, { passive: false });

      if (!self._overlayDragListenersAttached) {
        window.addEventListener('mousemove', function (e) {
          if (self._activeDragMove) self._activeDragMove(e);
        });
        window.addEventListener('touchmove', function (e) {
          if (self._activeDragMove) self._activeDragMove(e);
        }, { passive: false });

        window.addEventListener('mouseup', function (e) {
          if (self._activeDragEnd) self._activeDragEnd(e);
        });
        window.addEventListener('touchend', function (e) {
          if (self._activeDragEnd) self._activeDragEnd(e);
        });
        self._overlayDragListenersAttached = true;
      }

      itemEl.addEventListener('mousedown', function () {
        self._activeDragMove = onMove;
        self._activeDragEnd = function () {
          onEnd();
          self._activeDragMove = null;
          self._activeDragEnd = null;
        };
      });
      itemEl.addEventListener('touchstart', function () {
        self._activeDragMove = onMove;
        self._activeDragEnd = function () {
          onEnd();
          self._activeDragMove = null;
          self._activeDragEnd = null;
        };
      });
    },

    /**
     * Interactive Anchored Text / Overlay Resizing Engine (Canva / Photoshop Style)
     * Resizes actual text font size proportionally with stationary opposite-corner anchor
     */
    setupOverlayItemResize: function (itemEl, overlay) {
      var self = this;
      var handles = itemEl.querySelectorAll('.pe-resize-handle');
      var overlayLayer = document.getElementById('pe-overlay-layer');

      handles.forEach(function (handle) {
        var handleType = handle.getAttribute('data-resize-handle'); // tl, tr, br, bl, tc, bc, ml, mr

        var onResizeStart = function (e) {
          e.stopPropagation();
          e.preventDefault();

          self.selectOverlay(overlay.id);
          itemEl.classList.add('is-resizing');

          var p = e.touches ? e.touches[0] : e;
          var layerRect = overlayLayer ? overlayLayer.getBoundingClientRect() : null;
          if (!layerRect || layerRect.width <= 0 || layerRect.height <= 0) return;

          var textEl = itemEl.querySelector('.pe-overlay-text-content') || itemEl.querySelector('.pe-overlay-badge-content');
          var startFontSize = overlay.size || (overlay.type === 'badge' ? 20 : 28);

          // Current element bounding dimensions
          var itemRect = itemEl.getBoundingClientRect();
          var W_0 = itemRect.width;
          var H_0 = itemRect.height;
          if (W_0 <= 0) W_0 = 40;
          if (H_0 <= 0) H_0 = 20;

          // Current center in layer coordinates
          var currentCenterX = overlay.xRatio * layerRect.width;
          var currentCenterY = overlay.yRatio * layerRect.height;

          // Calculate stationary anchor point (in layer coordinates) based on which handle was grabbed
          var anchorX, anchorY;

          // Corner handles: opposite corner acts as stationary anchor point
          if (handleType === 'br') {
            anchorX = currentCenterX - W_0 / 2;
            anchorY = currentCenterY - H_0 / 2;
          } else if (handleType === 'tl') {
            anchorX = currentCenterX + W_0 / 2;
            anchorY = currentCenterY + H_0 / 2;
          } else if (handleType === 'tr') {
            anchorX = currentCenterX - W_0 / 2;
            anchorY = currentCenterY + H_0 / 2;
          } else if (handleType === 'bl') {
            anchorX = currentCenterX + W_0 / 2;
            anchorY = currentCenterY - H_0 / 2;
          }
          // Edge handles: opposite edge center acts as stationary anchor point
          else if (handleType === 'mr') {
            anchorX = currentCenterX - W_0 / 2;
            anchorY = currentCenterY;
          } else if (handleType === 'ml') {
            anchorX = currentCenterX + W_0 / 2;
            anchorY = currentCenterY;
          } else if (handleType === 'bc') {
            anchorX = currentCenterX;
            anchorY = currentCenterY - H_0 / 2;
          } else if (handleType === 'tc') {
            anchorX = currentCenterX;
            anchorY = currentCenterY + H_0 / 2;
          }

          var onResizeMove = function (ev) {
            var pt = ev.touches ? ev.touches[0] : ev;
            var mouseLayerX = pt.clientX - layerRect.left;
            var mouseLayerY = pt.clientY - layerRect.top;

            var scaleRatio = 1.0;

            if (handleType === 'br') {
              var dx = mouseLayerX - anchorX;
              var dy = mouseLayerY - anchorY;
              scaleRatio = (dx * W_0 + dy * H_0) / (W_0 * W_0 + H_0 * H_0);
            } else if (handleType === 'tl') {
              var dx = anchorX - mouseLayerX;
              var dy = anchorY - mouseLayerY;
              scaleRatio = (dx * W_0 + dy * H_0) / (W_0 * W_0 + H_0 * H_0);
            } else if (handleType === 'tr') {
              var dx = mouseLayerX - anchorX;
              var dy = anchorY - mouseLayerY;
              scaleRatio = (dx * W_0 + dy * H_0) / (W_0 * W_0 + H_0 * H_0);
            } else if (handleType === 'bl') {
              var dx = anchorX - mouseLayerX;
              var dy = mouseLayerY - anchorY;
              scaleRatio = (dx * W_0 + dy * H_0) / (W_0 * W_0 + H_0 * H_0);
            } else if (handleType === 'mr') {
              var dx = mouseLayerX - anchorX;
              scaleRatio = dx / W_0;
            } else if (handleType === 'ml') {
              var dx = anchorX - mouseLayerX;
              scaleRatio = dx / W_0;
            } else if (handleType === 'bc') {
              var dy = mouseLayerY - anchorY;
              scaleRatio = dy / H_0;
            } else if (handleType === 'tc') {
              var dy = anchorY - mouseLayerY;
              scaleRatio = dy / H_0;
            }

            if (isNaN(scaleRatio) || !isFinite(scaleRatio)) scaleRatio = 1.0;

            // Clamped between 12px and 140px
            var minSize = 12;
            var maxSize = 140;
            var newSize = Math.max(minSize, Math.min(maxSize, Math.round(startFontSize * scaleRatio)));

            // Update font size in state and DOM directly
            overlay.size = newSize;
            if (textEl) {
              textEl.style.fontSize = newSize + 'px';
            }

            // Measure updated rendered element dimensions
            var newRect = itemEl.getBoundingClientRect();
            var newW = newRect.width;
            var newH = newRect.height;

            // Calculate new center maintaining stationary anchor position
            var newCenterX, newCenterY;
            if (handleType === 'br') {
              newCenterX = anchorX + newW / 2;
              newCenterY = anchorY + newH / 2;
            } else if (handleType === 'tl') {
              newCenterX = anchorX - newW / 2;
              newCenterY = anchorY - newH / 2;
            } else if (handleType === 'tr') {
              newCenterX = anchorX + newW / 2;
              newCenterY = anchorY - newH / 2;
            } else if (handleType === 'bl') {
              newCenterX = anchorX - newW / 2;
              newCenterY = anchorY + newH / 2;
            } else if (handleType === 'mr') {
              newCenterX = anchorX + newW / 2;
              newCenterY = anchorY;
            } else if (handleType === 'ml') {
              newCenterX = anchorX - newW / 2;
              newCenterY = anchorY;
            } else if (handleType === 'bc') {
              newCenterX = anchorX;
              newCenterY = anchorY + newH / 2;
            } else if (handleType === 'tc') {
              newCenterX = anchorX;
              newCenterY = anchorY - newH / 2;
            }

            var newXRatio = Math.max(0.01, Math.min(0.99, newCenterX / layerRect.width));
            var newYRatio = Math.max(0.01, Math.min(0.99, newCenterY / layerRect.height));

            overlay.xRatio = newXRatio;
            overlay.yRatio = newYRatio;
            itemEl.style.left = (newXRatio * 100) + '%';
            itemEl.style.top = (newYRatio * 100) + '%';

            // Real-time synchronization with sidebar controls
            if (overlay.type === 'text') {
              var rangeInput = document.getElementById('pe-range-font-size');
              var valSpan = document.getElementById('pe-val-font-size');
              if (rangeInput) rangeInput.value = newSize;
              if (valSpan) valSpan.innerText = newSize + 'px';
              document.querySelectorAll('[data-quick-size]').forEach(function (pill) {
                if (parseInt(pill.getAttribute('data-quick-size'), 10) === newSize) {
                  pill.classList.add('active');
                } else {
                  pill.classList.remove('active');
                }
              });
            }
          };

          var onResizeEnd = function () {
            itemEl.classList.remove('is-resizing');
            window.removeEventListener('mousemove', onResizeMove);
            window.removeEventListener('touchmove', onResizeMove);
            window.removeEventListener('mouseup', onResizeEnd);
            window.removeEventListener('touchend', onResizeEnd);
            self.pushHistory();
          };

          window.addEventListener('mousemove', onResizeMove);
          window.addEventListener('touchmove', onResizeMove, { passive: false });
          window.addEventListener('mouseup', onResizeEnd);
          window.addEventListener('touchend', onResizeEnd);
        };

        handle.addEventListener('mousedown', onResizeStart);
        handle.addEventListener('touchstart', onResizeStart, { passive: false });
      });
    },

    selectOverlay: function (id) {
      this.selectedOverlayId = id;
      if (!this.state.overlays) return;
      var overlay = this.state.overlays.find(function (o) { return o.id === id; });
      if (!overlay) return;

      var allItems = document.querySelectorAll('.pe-overlay-item');
      allItems.forEach(function (el) {
        if (el.getAttribute('data-id') === id) {
          el.classList.add('selected');
        } else {
          el.classList.remove('selected');
        }
      });

      if (overlay.type === 'text') {
        var textTabBtn = document.querySelector('.pe-tab-btn[data-tab="text"]');
        if (textTabBtn && !textTabBtn.classList.contains('active')) {
          textTabBtn.click();
        }

        var textInput = document.getElementById('pe-text-string');
        var sizeInput = document.getElementById('pe-range-font-size');
        var fontFamilySelect = document.getElementById('pe-select-font-family');
        var colorInput = document.getElementById('pe-color-text');
        var bgInput = document.getElementById('pe-color-bg');
        var boldBtn = document.getElementById('pe-btn-text-bold');
        var italicBtn = document.getElementById('pe-btn-text-italic');
        var underlineBtn = document.getElementById('pe-btn-text-underline');
        var caseBtn = document.getElementById('pe-btn-text-case');
        var shadowBtn = document.getElementById('pe-btn-text-shadow');

        if (textInput) textInput.value = overlay.text;
        if (sizeInput) {
          var sz = overlay.size || 28;
          sizeInput.value = sz;
          document.getElementById('pe-val-font-size').innerText = sz + 'px';
          document.querySelectorAll('[data-quick-size]').forEach(function (pill) {
            if (parseInt(pill.getAttribute('data-quick-size'), 10) === sz) pill.classList.add('active');
            else pill.classList.remove('active');
          });
        }
        if (fontFamilySelect && overlay.fontFamily) {
          fontFamilySelect.value = overlay.fontFamily;
        }
        if (colorInput) colorInput.value = overlay.color || '#ffffff';
        if (bgInput) bgInput.value = (overlay.bgColor && overlay.bgColor !== 'transparent') ? overlay.bgColor : '#000000';
        if (boldBtn) {
          if (overlay.bold) boldBtn.classList.add('active');
          else boldBtn.classList.remove('active');
        }
        if (italicBtn) {
          if (overlay.italic) italicBtn.classList.add('active');
          else italicBtn.classList.remove('active');
        }
        if (underlineBtn) {
          if (overlay.underline) underlineBtn.classList.add('active');
          else underlineBtn.classList.remove('active');
        }
        if (caseBtn) {
          if (overlay.uppercase) caseBtn.classList.add('active');
          else caseBtn.classList.remove('active');
        }
        if (shadowBtn) {
          if (overlay.shadow) shadowBtn.classList.add('active');
          else shadowBtn.classList.remove('active');
        }

        // Align
        var align = overlay.align || 'center';
        var alignLeftBtn = document.getElementById('pe-btn-text-align-left');
        var alignCenterBtn = document.getElementById('pe-btn-text-align-center');
        var alignRightBtn = document.getElementById('pe-btn-text-align-right');
        [alignLeftBtn, alignCenterBtn, alignRightBtn].forEach(function (b) { if (b) b.classList.remove('active'); });
        if (align === 'left' && alignLeftBtn) alignLeftBtn.classList.add('active');
        if (align === 'center' && alignCenterBtn) alignCenterBtn.classList.add('active');
        if (align === 'right' && alignRightBtn) alignRightBtn.classList.add('active');
      }
    },

    deselectOverlay: function () {
      this.selectedOverlayId = null;
      var allItems = document.querySelectorAll('.pe-overlay-item');
      allItems.forEach(function (el) { el.classList.remove('selected'); });
    },

    deleteOverlay: function (id) {
      if (!this.state.overlays) return;
      this.state.overlays = this.state.overlays.filter(function (o) { return o.id !== id; });
      if (this.selectedOverlayId === id) {
        this.selectedOverlayId = null;
      }
      this.renderOverlaysDOM();
      this.pushHistory();
    },

    duplicateSelectedOverlay: function () {
      if (!this.selectedOverlayId || !this.state.overlays) return;
      var self = this;
      var orig = this.state.overlays.find(function (o) { return o.id === self.selectedOverlayId; });
      if (!orig) return;

      var copy = JSON.parse(JSON.stringify(orig));
      copy.id = (copy.type || 'item') + '_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
      copy.xRatio = Math.min(0.92, copy.xRatio + 0.05);
      copy.yRatio = Math.min(0.92, copy.yRatio + 0.05);

      this.state.overlays.push(copy);
      this.renderOverlaysDOM();
      this.selectOverlay(copy.id);
      this.pushHistory();
    },

    centerSelectedOverlay: function () {
      if (!this.selectedOverlayId || !this.state.overlays) return;
      var self = this;
      var item = this.state.overlays.find(function (o) { return o.id === self.selectedOverlayId; });
      if (!item) return;

      item.xRatio = 0.5;
      item.yRatio = 0.5;
      this.renderOverlaysDOM();
      this.selectOverlay(item.id);
      this.pushHistory();
    },

    clearAllOverlays: function () {
      if (!this.state.overlays || !this.state.overlays.length) return;
      this.state.overlays = [];
      this.selectedOverlayId = null;
      this.renderOverlaysDOM();
      this.pushHistory();
    },

    bakeOverlaysToCanvas: function (targetCanvas, targetCtx) {
      var canvas = targetCanvas || this.canvas;
      var ctx = targetCtx || this.ctx;
      if (!this.state.overlays || !this.state.overlays.length) return;

      var overlayLayer = document.getElementById('pe-overlay-layer');
      var displayWidth = (overlayLayer && overlayLayer.clientWidth) ? overlayLayer.clientWidth : (canvas.clientWidth || canvas.width);
      var scale = canvas.width / displayWidth;

      for (var i = 0; i < this.state.overlays.length; i++) {
        var o = this.state.overlays[i];
        var x = o.xRatio * canvas.width;
        var y = o.yRatio * canvas.height;

        ctx.save();
        if (o.type === 'text') {
          var scaledSize = Math.max(12, Math.round((o.size || 28) * scale));
          var fontFamily = o.fontFamily || 'sans-serif';
          var fontStyle = (o.italic ? 'italic ' : '') + (o.bold ? 'bold ' : '') + scaledSize + 'px ' + fontFamily;
          ctx.font = fontStyle;
          ctx.textBaseline = 'middle';
          var align = o.align || 'center';
          ctx.textAlign = align;

          var textToDraw = o.uppercase ? (o.text || '').toUpperCase() : (o.text || '');
          var metrics = ctx.measureText(textToDraw);
          var textWidth = metrics.width;
          var textHeight = scaledSize * 1.25;

          if (o.bgColor && o.bgColor !== 'transparent') {
            ctx.fillStyle = o.bgColor;
            var padX = Math.round(14 * scale);
            var padY = Math.round(8 * scale);
            var rx;
            if (align === 'left') {
              rx = x - padX;
            } else if (align === 'right') {
              rx = x - textWidth - padX;
            } else {
              rx = x - (textWidth / 2) - padX;
            }
            var ry = y - (textHeight / 2) - padY;
            var rw = textWidth + (padX * 2);
            var rh = textHeight + (padY * 2);
            this.roundRect(ctx, rx, ry, rw, rh, Math.round(8 * scale));
            ctx.fill();
          }

          if (o.shadow) {
            ctx.shadowColor = '#000000';
            ctx.shadowBlur = Math.round(6 * scale);
            ctx.shadowOffsetX = 0;
            ctx.shadowOffsetY = Math.round(2 * scale);
          }

          ctx.fillStyle = o.color || '#FFFFFF';
          ctx.fillText(textToDraw, x, y);

          // Underline support
          if (o.underline) {
            ctx.strokeStyle = o.color || '#FFFFFF';
            ctx.lineWidth = Math.max(1, Math.round(2 * scale));
            var ulStartX = align === 'left' ? x : (align === 'right' ? x - textWidth : x - (textWidth / 2));
            var ulEndX = ulStartX + textWidth;
            var ulY = y + (textHeight / 2) - Math.round(2 * scale);
            ctx.beginPath();
            ctx.moveTo(ulStartX, ulY);
            ctx.lineTo(ulEndX, ulY);
            ctx.stroke();
          }
        } else if (o.type === 'badge') {
          var badgeFontSize = Math.max(14, Math.round((o.size || 20) * scale));
          ctx.font = 'bold ' + badgeFontSize + 'px sans-serif';
          ctx.textBaseline = 'middle';
          ctx.textAlign = 'center';

          var bMetrics = ctx.measureText(o.text);
          var bWidth = bMetrics.width;
          var bPadX = Math.round(16 * scale);
          var bPadY = Math.round(10 * scale);
          var brw = bWidth + (bPadX * 2);
          var brh = badgeFontSize + (bPadY * 2);

          ctx.fillStyle = o.bgColor || '#E11D48';
          var bx = x - (brw / 2);
          var by = y - (brh / 2);
          this.roundRect(ctx, bx, by, brw, brh, Math.round(6 * scale));
          ctx.fill();

          ctx.fillStyle = o.color || '#FFFFFF';
          ctx.fillText(o.text, x, y);
        }
        ctx.restore();
      }
    },

    /**
     * Attach Photo Editor to an HTML File Input element.
     * When user selects files, offers instant preview and editing.
     */
    attach: function (inputElement, options) {
      if (!inputElement) return;
      options = options || {};
      var self = this;

      inputElement.addEventListener('change', function (e) {
        if (!this.files || !this.files.length) return;
        var file = this.files[0];
        if (!file.type.match(/^image\//)) return;

        if (options.autoOpen) {
          self.open({
            file: file,
            onSave: function (blob, newFile, dataUrl) {
              try {
                var dt = new DataTransfer();
                dt.items.add(newFile);
                inputElement.files = dt.files;
              } catch (err) {
                console.warn('DataTransfer not supported by browser');
              }

              if (options.onSave) {
                options.onSave(blob, newFile, dataUrl);
              }
            }
          });
        }
      });
    }
  };

  window.PhotoEditor = PhotoEditor;

})(window, document);
