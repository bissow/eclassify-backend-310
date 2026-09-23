@extends('layouts.main')

@section('title')
    {{ __('Message') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section admin-chat-ui">
        <div class="row">
            <div class="col-12">
                <div class="card chat-main-card">
                    <div class="card-body p-2 p-md-3 h-100">
                        <div class="row g-0 h-100 m-0 w-100">
                            <!-- Products Section -->
                            <div class="col-xl-4 col-lg-4 col-md-4 col-12 h-100 px-2 pb-3 pb-md-0">
                                <div class="d-flex flex-column chat-sidebar chat-panel h-100 w-100">
                                <div class="p-3 flex-shrink-0 d-flex justify-content-between align-items-center panel-head">
                                    <h5 class="mb-0 panel-title">{{ __('Products') }}</h5>
                                    <a href="javascript:void(0)" class="text-muted admin-chat-refresh-icon" id="refresh-products-btn" title="{{ __('Refresh Products') }}">
                                        <i class="fas fa-sync-alt"></i>
                                    </a>
                                </div>
                                <div class="p-3 border-bottom flex-shrink-0">
                                    <div class="input-group admin-chat-search-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" id="product-search" placeholder="{{ __('Search...') }}">
                                    </div>
                                </div>
                                <div id="products-list" class="flex-grow-1 chat-scrollable chat-scroll-hide">
                                    <!-- Products will be loaded here -->
                                </div>
                                </div>
                            </div>

                            <!-- Chats Section -->
                            <div class="col-xl-3 col-lg-3 col-md-4 col-12 h-100 px-2 pb-3 pb-md-0">
                                <div class="d-flex flex-column chat-sidebar chat-panel h-100 w-100">
                                <div class="p-3 border-bottom flex-shrink-0 d-flex justify-content-between align-items-center panel-head">
                                    <div>
                                        <h5 class="mb-0 panel-title">{{ __('Chats') }}</h5>
                                        <small class="text-muted chat-summary" id="chats-summary">0 {{ __('Message') }}, 0 {{ __('Unread') }}</small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="javascript:void(0)" class="text-muted admin-chat-refresh-icon" id="refresh-chats-btn" title="{{ __('Refresh Chats') }}">
                                            <i class="fas fa-sync-alt"></i>
                                        </a>
                                        <a href="javascript:void(0)" class="text-muted small" id="chat-select-toggle-btn" onclick="window.adminChatToggleSelectMode()" title="{{ __('Select') }}">
                                            <i class="fas fa-check-circle"></i>
                                        </a>
                                    </div>
                                </div>
                                <div class="p-2 border-bottom flex-shrink-0">
                                    <div class="input-group admin-chat-search-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" id="chat-search" placeholder="{{ __('Search...') }}">
                                    </div>
                                </div>
                                <div id="chats-list" class="flex-grow-1 chat-scrollable chat-scroll-hide">
                                    <div class="text-center p-4 mt-5">
                                        <img src="{{ asset('assets/images/chats/chat-section-emtpy.png') }}" alt="{{ __('Select a item to chat') }}" class="admin-chat-empty-icon">
                                        <h5 class="admin-chat-empty-title">{{ __('Select a item to chat') }}</h5>
                                        <p class="text-muted admin-chat-empty-subtitle">{{ __('Pick a item from the list to view its chats.') }}</p>
                                    </div>
                                </div>
                                <div class="p-2 border-top flex-shrink-0 chat-select-actions" id="chat-select-actions">
                                    <button class="btn btn-sm btn-outline-secondary flex-grow-1" onclick="window.adminChatToggleSelectMode()">{{ __('Cancel') }}</button>
                                    <button class="btn btn-sm btn-danger flex-grow-1" id="chat-delete-selected-btn" onclick="window.adminChatDeleteSelectedChats()" disabled>{{ __('Delete') }} (<span id="chat-selected-count">0</span>)</button>
                                </div>
                                </div>
                            </div>

                            <!-- Chat Window Section -->
                            <div class="col-xl-5 col-lg-5 col-md-4 col-12 h-100 px-2 pb-3 pb-md-0">
                                <div class="d-flex flex-column chat-window chat-panel h-100 w-100">
                                @if(!$firebaseWebConfigured)
                                <div class="alert alert-info m-3 mb-0" role="alert">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>{{ __('Live Chat Setup Required') }}:</strong> 
                                    {{ __('To enable real-time chat refresh, please configure Firebase web settings.') }}
                                    <a href="{{ route('settings.firebase.index') }}" class="alert-link ms-1">{{ __('Go to Firebase Settings') }}</a>
                                    <div class="small mt-2">
                                        {{ __('Required fields: Api Key, Auth Domain, Project Id, Storage Bucket, Messaging Sender Id, App Id, VAPID Key') }}
                                    </div>
                                </div>
                                @elseif(!empty($firebaseWebConfig['vapidKey']) == false)
                                <div class="alert alert-warning m-3 mb-0" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>{{ __('VAPID Key Missing') }}:</strong> 
                                    {{ __('VAPID key is required for FCM token. Please add it in Firebase Settings.') }}
                                    <a href="{{ route('settings.firebase.index') }}" class="alert-link ms-1">{{ __('Go to Firebase Settings') }}</a>
                                    <div class="small mt-2">
                                        {{ __('Get VAPID key from: Firebase Console → Project Settings → Cloud Messaging → Web Push certificates') }}
                                    </div>
                                </div>
                                @endif
                                <div class="p-3 border-bottom d-none justify-content-between align-items-center flex-shrink-0 chat-user-head" id="chat-header">
                                    <div class="d-flex align-items-center">
                                        <span id="chat-user-avatar-wrapper" class="me-2"></span>
                                        <div>
                                            <h6 class="mb-0" id="chat-user-name"></h6>
                                            <small class="text-muted" id="chat-item-name"></small>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold text-primary me-2" id="chat-item-price"></span>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-link" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="window.adminChatToggleMessageSelectMode()"><i class="fas fa-check-square me-2"></i>{{ __('Select Messages') }}</a></li>
                                            <li id="block-user-menu-item"><a class="dropdown-item text-warning" href="javascript:void(0)" onclick="window.adminChatBlockUser()"><i class="fas fa-ban me-2"></i>{{ __('Block User') }}</a></li>
                                            <li id="unblock-user-menu-item" style="display: none;"><a class="dropdown-item text-success" href="javascript:void(0)" onclick="window.adminChatUnblockUser()"><i class="fas fa-check-circle me-2"></i>{{ __('Unblock User') }}</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="window.adminChatDeleteChat()"><i class="fas fa-trash-alt me-2"></i>{{ __('Delete Chat') }}</a></li>
                                        </ul>
                                    </div>
                                    </div>
                                </div>
                                <div class="p-3 border-bottom d-none justify-content-between align-items-center flex-shrink-0" id="chat-offer-banner">
                                    <div>
                                        <div class="fw-medium admin-chat-offer-banner-title" id="chat-offer-banner-title"></div>
                                        <small class="text-muted" id="chat-offer-banner-subtitle"></small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary" id="chat-offer-banner-btn" onclick="window.adminChatOpenOfferModal()"></button>
                                </div>
                                <div id="chat-messages" class="flex-grow-1 chat-scrollable chat-messages-container d-flex flex-column align-items-center justify-content-center chat-scroll-hide">
                                    <!-- Blocked User Banner -->
                                    <div id="blocked-user-banner" class="alert alert-warning w-100 mb-2" style="display: none;" role="alert">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-ban me-2"></i>
                                                <strong>{{ __('You have blocked this user') }}</strong>
                                                <p class="mb-0 small">
                                                    {{ __('Click') }} <a href="javascript:void(0)" onclick="window.adminChatUnblockUser()" class="fw-bold text-decoration-underline">{{ __('here') }}</a> {{ __('to unblock') }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Messages Area -->
                                    <div id="chat-messages-list" class="w-100">
                                    </div>
                                    <!-- Empty State -->
                                    <div class="text-center p-4 chat-empty-state">
                                        <img src="{{ asset('assets/images/chats/message-section-empty.png') }}" alt="{{ __('No conversation selected yet!') }}" class="admin-chat-msg-empty-icon">
                                        <h4 class="admin-chat-msg-empty-title">{{ __('No conversation selected yet!') }}</h4>
                                        <p class="text-muted mt-2 admin-chat-msg-empty-subtitle">{{ __('Pick a conversation from the sidebar to view details and messages.') }}</p>
                                    </div>
                                </div>
                                <div class="p-2 border-top flex-shrink-0 msg-select-actions" id="msg-select-actions">
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-secondary flex-grow-1" onclick="window.adminChatToggleMessageSelectMode()">{{ __('Cancel') }}</button>
                                        <button class="btn btn-sm btn-danger flex-grow-1" id="msg-delete-selected-btn" onclick="window.adminChatDeleteSelectedMessages()" disabled>{{ __('Delete') }} (<span id="msg-selected-count">0</span>)</button>
                                    </div>
                                </div>
                                <div class="p-3 border-top flex-shrink-0" id="chat-input-container">
                                    <!-- Link Preview (shown while composing, hidden if a file is attached) -->
                                    <div id="compose-link-preview"></div>
                                    <!-- File Preview Bar (2 Rows) -->
                                    <div id="file-preview-bar">
                                        <button type="button" id="cancel-file-btn" class="file-preview-cancel-btn" title="{{ __('Remove attachment') }}">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <!-- Row 1: Full Image Preview (not clipped) -->
                                        <div class="file-preview-row-media">
                                            <img id="file-preview-thumb" src="" alt="{{ __('Preview') }}" style="display: none;">
                                        </div>
                                        <!-- Row 2: File Name & Meta -->
                                        <div class="file-preview-row-info">
                                            <div class="file-preview-name-wrap">
                                                <i class="fas fa-image text-primary"></i>
                                                <span class="file-preview-filename" id="file-preview-name"></span>
                                            </div>
                                            <div class="file-preview-meta">
                                                <span class="file-preview-tag" id="file-preview-type">JPG</span>
                                                <span class="file-preview-size" id="file-preview-size">0 KB</span>
                                            </div>
                                        </div>
                                    </div>
                                    <form id="send-message-form">
                                        <div class="input-group chat-compose-group">
                                            <button type="button" class="btn btn-outline-secondary compose-icon-btn" id="attach-file-btn" title="{{ __('Attach file') }}">
                                                @if(file_exists(public_path('assets/images/chats/attach-file-icon.png'))) <img src="{{ asset('assets/images/chats/attach-file-icon.png') }}" alt="{{ __('Attach file') }}"> @else <i class="fas fa-paperclip"></i> @endif
                                            </button>
                                            <!-- Record Audio Button -->
                                            <button type="button" class="btn btn-outline-secondary compose-icon-btn" id="record-audio-btn" title="{{ __('Record Audio') }}">
                                                <i class="fas fa-microphone"></i>
                                            </button>
                                            <!-- Recording UI (hidden by default) -->
                                            <div id="recording-ui" class="d-none align-items-center justify-content-between flex-grow-1 bg-light rounded px-2">
                                                <div class="d-flex align-items-center">
                                                    <span class="text-danger me-2"><i class="fas fa-circle"></i></span>
                                                    <span id="recording-timer">0:00</span>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <button type="button" class="btn btn-sm btn-link text-secondary" id="pause-recording-btn" title="{{ __('Pause') }}">
                                                        <i class="fas fa-pause"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-link text-danger" id="stop-recording-btn" title="{{ __('Stop') }}">
                                                        <i class="fas fa-stop"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-link text-secondary" id="cancel-recording-btn" title="{{ __('Cancel') }}">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <!-- Audio Preview UI (hidden by default) -->
                                            <div id="audio-preview-ui" class="d-none align-items-center flex-grow-1">
                                                <audio id="recorded-audio-preview" controls class="flex-grow-1 me-2" style="height: 35px;"></audio>
                                                <button type="button" class="btn btn-sm btn-link text-danger" id="delete-recorded-audio-btn" title="{{ __('Delete') }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <button type="button" class="btn btn-primary compose-send-btn" id="send-recorded-audio-btn" title="{{ __('Send') }}">
                                                    @if(file_exists(public_path('assets/images/chats/send-icon.png'))) <img src="{{ asset('assets/images/chats/send-icon.png') }}" alt="{{ __('Send') }}"> @else <i class="fas fa-paper-plane"></i> @endif
                                                </button>
                                            </div>
                                            <!-- Message Input (hidden when recording or preview) -->
                                            <textarea class="form-control" id="message-input" placeholder="{{ __('Type Messages') }}" rows="1" style="resize: none; overflow-y: auto; max-height: 88px;"></textarea>
                                            <input type="file" id="file-input" accept=".jpg, .jpeg, .png, image/jpeg, image/png" style="display: none;">
                                            <input type="file" id="audio-input" accept="audio/*" style="display: none;">
                                            <button type="submit" class="btn btn-primary compose-send-btn" id="send-btn">
                                                @if(file_exists(public_path('assets/images/chats/send-icon.png'))) <img src="{{ asset('assets/images/chats/send-icon.png') }}" alt="{{ __('Send') }}"> @else <i class="fas fa-paper-plane"></i> @endif
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Make / Edit Offer Modal -->
    <div class="modal fade" id="offer-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-4 position-relative">
                    <button type="button" class="btn-close position-absolute" style="top: 16px; right: 16px;" data-bs-dismiss="modal" aria-label="Close"></button>
                    <h3 class="mb-1" id="offer-modal-title"></h3>
                    <p class="text-muted mb-4">{{ __("Open to offers! Let's chat!") }}</p>
                    <div class="admin-chat-offer-seller-price mb-4 text-center">
                        <div class="text-muted small">{{ __('Seller Price') }}</div>
                        <div class="admin-chat-offer-seller-price-amount" id="offer-modal-seller-price"></div>
                    </div>
                    <form id="offer-form">
                        <div class="mb-3">
                            <label for="offer-amount-input" class="form-label fw-semibold">{{ __('Your Offer') }}</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="offer-amount-input" placeholder="{{ __('Type Offer Price') }}" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="offer-submit-btn">{{ __('Send Offer') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')

    <script>
        (function() {
            'use strict';
            
            // Admin Chat Configuration
            const config = {
                currentUserId: {{ $adminUser->id ?? Auth::id() }},
                placeholderImage: '{{ asset("assets/images/logo/placeholder.png") }}',
                routes: {
                    products: '{{ route("admin-chat.products") }}',
                    chatList: '{{ route("admin-chat.chat-list") }}',
                    messages: '{{ route("admin-chat.messages") }}',
                    sendMessage: '{{ route("admin-chat.send-message") }}',
                    sendOffer: '{{ route("admin-chat.send-offer") }}',
                    deleteChat: '{{ route("admin-chat.delete-chat") }}',
                    deleteMessages: '{{ route("admin-chat.delete-messages") }}',
                    blockUser: '{{ route("admin-chat.block-user") }}',
                    unblockUser: '{{ route("admin-chat.unblock-user") }}',
                    linkPreview: '{{ route("admin-chat.link-preview") }}'
                },
                translations: {
                    noProducts: '{{ __("No products found") }}',
                    noChats: '{{ __("No chats found") }}',
                    noMessages: '{{ __("No messages yet") }}',
                    selectChat: '{{ __("Select a chat to start messaging") }}',
                    unknownUser: '{{ __("Unknown User") }}',
                    failedProducts: '{{ __("Failed to load products") }}',
                    failedChats: '{{ __("Failed to load chats") }}',
                    failedMessages: '{{ __("Failed to load messages") }}',
                    failedSend: '{{ __("Failed to send message") }}',
                    invalidFileType: '{{ __("The file must be a file of type: jpg, jpeg, png.") }}',
                    selectChatFirst: '{{ __("Please select a chat") }}',
                    message: '{{ __("Message") }}',
                    unread: '{{ __("Unread") }}',
                    noChatFoundTitle: '{{ __("No Chat Found") }}',
                    noChatFoundDesc: '{{ __("It Look\'s Like there is no Chat Available") }}',
                    noMessageSelectedTitle: '{{ __("No conversation selected yet!") }}',
                    noMessageSelectedDesc: '{{ __("Pick a conversation from the sidebar to view details and messages.") }}',
                    confirmDeleteChat: '{{ __("Are you sure you want to delete this chat?") }}',
                    confirmDeleteMessage: '{{ __("Are you sure you want to delete this message?") }}',
                    chatDeleted: '{{ __("Chat Deleted Successfully") }}',
                    messageDeleted: '{{ __("Message Deleted Successfully") }}',
                    failedDeleteChat: '{{ __("Failed to delete chat") }}',
                    failedDeleteMessage: '{{ __("Failed to delete message") }}',
                    confirmDeleteSelectedChats: '{{ __("Are you sure you want to delete the selected chats?") }}',
                    confirmDeleteSelectedMessages: '{{ __("Are you sure you want to delete the selected messages?") }}',
                    noChatsSelected: '{{ __("No chats selected") }}',
                    noMessagesSelected: '{{ __("No messages selected") }}',
                    loadingPreview: '{{ __("Loading preview...") }}'
                },
                images: {
                    emptyChats: '{{ asset("assets/images/chats/chat-section-emtpy.png") }}',
                    emptyMessages: '{{ asset("assets/images/chats/message-section-empty.png") }}'
                },
                firebaseWebConfig: @json($firebaseWebConfig ?? []),
                firebaseWebConfigured: {{ $firebaseWebConfigured ? 'true' : 'false' }}
            };
            
            // Variables
            let selectedProductId = null;
            let selectedChatId = null;
            let currentUserId = config.currentUserId;
            let productsPage = 1;
            let productsHasMore = true;
            let productsLoading = false;
            let productsSearch = '';
            let messagesLoading = false;
            let currentChatId = null;
            let currentOffer = null;
            let hasSentOffer = false;
            let lastOwnOfferAmount = null;
            let chatsPage = 1;
            let chatsHasMore = true;
            let chatsLoading = false;
            let chatsSearch = '';
            let chatSelectMode = false;
            let selectedChatIds = new Set();
            let messageSelectMode = false;
            let selectedMessageIds = new Set();
            let selectedChatUserName = '';
            let selectedChatUserImage = '';
            const linkPreviewCache = new Map(); // url -> preview data (or null once known to have none)
            const linkPreviewInFlight = new Set(); // urls currently being fetched

            // Firebase (app init, service worker, token registration) is handled
            // globally in layouts/footer_script.blade.php so notifications also
            // work on other admin screens. This page only reacts to the
            // 'fcm:message' / 'fcm:sw-broadcast' events it dispatches, and marks
            // itself active so the global handler doesn't also pop a browser
            // notification for a chat this page is already showing.
            window.__fcmChatPageActive = true;

            // Function to handle chat notifications (from foreground or service worker)
            function handleChatNotification(payload) {
                const data = payload.data || payload.notification?.data || {};
                const notificationData = payload.notification || {};
                const itemOfferId = parseInt(
                    data.item_offer_id ||
                    payload.data?.item_offer_id ||
                    payload.notification?.data?.item_offer_id ||
                    0
                );


                if (itemOfferId > 0) {
                    refreshChatOnNotification(itemOfferId);

                    // Show browser notification for foreground messages
                    // (background notifications are handled by the service worker)
                    // Only show if this chat is not currently open
                    if (currentChatId !== itemOfferId && 'Notification' in window && Notification.permission === 'granted') {
                        const title = data.title || notificationData.title || 'New Message';
                        const body = data.body || notificationData.body || 'You have a new chat message';
                        const icon = data.icon || notificationData.icon || '';
                        const notification = new Notification(title, {
                            body: body,
                            icon: icon,
                            data: { click_action: data.click_action || '' }
                        });
                        notification.onclick = function() {
                            window.focus();
                            notification.close();
                        };
                        // Auto-close after 5 seconds
                        setTimeout(() => notification.close(), 5000);
                    }
                }
            }
            
            // Function to refresh chat when notification is received
            function refreshChatOnNotification(itemOfferId) {
                // If the current chat matches, refresh messages
                if (currentChatId === itemOfferId) {
                    if (!messagesLoading) {
                        // Fetch messages first so the backend marks them as "read".
                        // Wait for it to finish, then reload the chat list to get the updated (zero) unread count.
                        loadMessages(currentChatId, true, function() {
                            if (selectedProductId) {
                                loadChatList(selectedProductId, false, chatsSearch);
                            }
                            // Also refresh product list so the unread pointer disappears
                            productsPage = 1;
                            productsHasMore = true;
                            loadProducts(productsSearch, false);
                        });
                    }
                } else {
                    // Chat is not currently open, just refresh the list to show the new unread count
                    if (selectedProductId) {
                        loadChatList(selectedProductId, false, chatsSearch);
                    }
                    // Refresh product list so the unread pointer appears
                    productsPage = 1;
                    productsHasMore = true;
                    loadProducts(productsSearch, false);
                }
            }

            // Foreground FCM messages (global init lives in footer_script.blade.php)
            window.addEventListener('fcm:message', (e) => handleChatNotification(e.detail));
            // Relayed by the service worker when it handled a background push itself
            window.addEventListener('fcm:sw-broadcast', (e) => {
                const itemOfferId = parseInt(e.detail && e.detail.itemOfferId || 0);
                if (itemOfferId > 0) refreshChatOnNotification(itemOfferId);
            });

            // Listen for window/tab becoming visible again after being minimized/hidden.
            // Only refetch after a real away period (not a quick alt-tab or opening
            // a link in a new tab), otherwise the chat list/messages flicker on every click.
            const VISIBILITY_REFRESH_THRESHOLD_MS = 15000;
            let hiddenAt = null;
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    hiddenAt = Date.now();
                    return;
                }
                const wasAwayLongEnough = hiddenAt !== null && (Date.now() - hiddenAt) >= VISIBILITY_REFRESH_THRESHOLD_MS;
                hiddenAt = null;
                if (wasAwayLongEnough) {
                    // Refetch messages and chats directly from server when turning on tab to catch anything that was missed
                    if (selectedProductId && !chatsLoading) {
                        loadChatList(selectedProductId, false, chatsSearch);
                    }
                    if (currentChatId && !messagesLoading) {
                        loadMessages(currentChatId, true);
                    }
                }
            });

            $(document).ready(function() {
                // Initial load
                loadProducts();
                
                // Force scrollbar visibility check after a short delay
                setTimeout(function() {
                    const productsList = document.getElementById('products-list');
                    if (productsList && productsList.scrollHeight > productsList.clientHeight) {
                        productsList.style.overflowY = 'scroll';
                    }
                }, 500);
                
                
                // Product search with debounce
                let searchTimeout;
                $('#product-search').on('input', function() {
                    clearTimeout(searchTimeout);
                    const search = $(this).val();
                    productsSearch = search;
                    productsPage = 1;
                    productsHasMore = true;
                    $('#products-list').empty();
                    searchTimeout = setTimeout(function() {
                        loadProducts(search);
                    }, 500);
                });

                // Scroll pagination for products
                $('#products-list').on('scroll', function() {
                    if (productsLoading || !productsHasMore) return;
                    
                    const container = $(this);
                    const scrollTop = container.scrollTop();
                    const scrollHeight = container[0].scrollHeight;
                    const clientHeight = container[0].clientHeight;
                    
                    // Load more when 50px from bottom
                    if (scrollHeight - scrollTop - clientHeight <= 50) {
                        loadProducts(productsSearch, true);
                    }
                });

                // Scroll pagination for chats
                $('#chats-list').on('scroll', function() {
                    if (chatsLoading || !chatsHasMore || !selectedProductId) return;
                    
                    const container = $(this);
                    const scrollTop = container.scrollTop();
                    const scrollHeight = container[0].scrollHeight;
                    const clientHeight = container[0].clientHeight;
                    
                    // Load more when 50px from bottom
                    if (scrollHeight - scrollTop - clientHeight <= 50) {
                        loadChatList(selectedProductId, true, chatsSearch);
                    }
                });

                // Chat search with debounce
                let chatSearchTimeout;
                $('#chat-search').on('input', function() {
                    clearTimeout(chatSearchTimeout);
                    const search = $(this).val();
                    chatsSearch = search;
                    chatsPage = 1;
                    chatsHasMore = true;
                    $('#chats-list .chat-item').not('.empty-state').remove();
                    chatSearchTimeout = setTimeout(function() {
                        if (selectedProductId) {
                            loadChatList(selectedProductId, false, chatsSearch);
                        }
                    }, 500);
                });

                // Refresh products button
                $('#refresh-products-btn').on('click', function() {
                    const btn = $(this);
                    btn.addClass('refreshing');

                    productsPage = 1;
                    productsHasMore = true;
                    productsLoading = false;
                    $('#products-list').empty();

                    loadProducts(productsSearch, false);
                    setTimeout(function() {
                        btn.removeClass('refreshing');
                    }, 600);
                });

                // Refresh chats button
                $('#refresh-chats-btn').on('click', function() {
                    if (!selectedProductId) return;
                    
                    const btn = $(this);
                    btn.addClass('refreshing');
                    
                    // Reset pagination
                    chatsPage = 1;
                    chatsHasMore = true;
                    chatsLoading = false;
                    $('#chats-list .chat-item').not('.empty-state').remove();
                    
                    loadChatList(selectedProductId, false, chatsSearch, function() {
                        btn.removeClass('refreshing');
                    });
                });

                // Send message form
                $('#send-message-form').on('submit', function(e) {
                    e.preventDefault();
                    sendMessage();
                });

                // Make / Edit offer form
                $('#offer-form').on('submit', function(e) {
                    e.preventDefault();
                    submitOffer();
                });

                // Show a link preview under the compose box while typing a message
                // that contains a URL. An attached image takes priority — no link
                // preview is shown/fetched while a file is selected.
                let composePreviewTimeout;
                $('#message-input').on('input', function() {
                    clearTimeout(composePreviewTimeout);
                    const text = $(this).val();
                    composePreviewTimeout = setTimeout(function() {
                        updateComposeLinkPreview(text);
                    }, 500);

                    // Auto-expand textarea up to 5 lines, then scroll internally
                    this.style.height = 'auto';
                    const lineHeight = parseFloat(getComputedStyle(this).lineHeight) || 22;
                    const maxHeight = lineHeight * 4;
                    this.style.height = Math.min(this.scrollHeight, maxHeight) + 'px';
                });

                // Enter sends message, Shift+Enter adds a newline
                $('#message-input').on('keydown', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        $('#send-message-form').trigger('submit');
                    }
                });

                // Attach file button
                $('#attach-file-btn').on('click', function() {
                    $('#file-input').click();
                });

                // Cancel file selection
                $('#cancel-file-btn').on('click', function() {
                    clearFilePreview();
                });

                $('#file-input').on('change', function() {
                    if (this.files.length > 0) {
                        const file = this.files[0];
                        const validExts = ['jpg', 'jpeg', 'png'];
                        const ext = (file.name.split('.').pop() || '').toLowerCase();
                        if (!validExts.includes(ext)) {
                            showErrorToast(config.translations.invalidFileType);
                            clearFilePreview();
                            return;
                        }
                        showFilePreview(file);
                    }
                });

                // Remove the compose link preview (delegated: card is added dynamically)
                $('#compose-link-preview').on('click', '#compose-link-preview-remove', function() {
                    $('#compose-link-preview').empty();
                });

                // Cleanup on page unload (if needed in future)
            });

            function loadProducts(search = '', append = false) {
                if (productsLoading) return;
                if (append && !productsHasMore) return;
                
                productsLoading = true;
                
                const pageToLoad = append ? productsPage + 1 : 1;
                
                $.ajax({
                    url: config.routes.products,
                    method: 'GET',
                    cache: false,
                    data: {
                        search: search,
                        page: pageToLoad
                    },
                    success: function(response) {
                        productsLoading = false;
                        if (response.error === false) {
                            const data = response.data;
                            productsHasMore = data.has_more || false;
                            
                            if (append) {
                                productsPage = pageToLoad;
                                renderProducts(data.data, true);
                            } else {
                                productsPage = data.current_page;
                                renderProducts(data.data, false);
                            }
                            
                            // Ensure scrollbar is visible if content overflows
                            setTimeout(function() {
                                const productsList = document.getElementById('products-list');
                                if (productsList && productsList.scrollHeight > productsList.clientHeight) {
                                    productsList.style.overflowY = 'scroll';
                                }
                            }, 100);
                        }
                    },
                    error: function() {
                        productsLoading = false;
                        if (!append) {
                            showErrorToast(config.translations.failedProducts);
                        }
                    }
                });
            }

            function renderProducts(products, append = false) {
                const container = $('#products-list');
                
                if (!append) {
                    container.empty();
                }

                if (products.length === 0 && !append) {
                    container.html('<div class="text-center p-4 text-muted">' + config.translations.noProducts + '</div>');
                    return;
                }

                products.forEach(function(product) {
                    const baseUrl = (typeof window.baseurl !== 'undefined' ? window.baseurl : window.location.origin + '/').replace(/\/$/, '');
                    const productImage = product.image ? (product.image.startsWith('http') ? product.image : baseUrl + '/' + product.image.replace(/^\//, '')) : config.placeholderImage;
                    
                    // Use formatted price from backend, fallback to raw price if formatted_price is empty
                    let priceDisplay = '';
                    if (product.formatted_price) {
                        priceDisplay = '<span class="fw-medium admin-chat-product-price">' + escapeHtml(product.formatted_price) + '</span>';
                    } else if (product.price && product.price > 0) {
                        // Fallback: format price manually if formatted_price is not available
                        const price = parseFloat(product.price) || 0;
                        priceDisplay = '<span class="fw-medium admin-chat-product-price">$ ' + price.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 2}) + '</span>';
                    }
                    
                    const unreadBadge = (product.unread_chat_count && parseInt(product.unread_chat_count) > 0) 
                        ? `<div class="unread-badge shadow-sm">${parseInt(product.unread_chat_count)}</div>` 
                        : '';
                        
                    let chattersHtml = '';
                    if (product.chatters && product.chatters.length > 0) {
                        const maxAvatars = 4;
                        const displayAvatars = product.chatters.slice(0, maxAvatars);
                        const remaining = product.chatters.length - maxAvatars;
                        
                        displayAvatars.forEach((profile, idx) => {
                            let zIndex = 5 - idx;
                            let margin = idx === 0 ? '0' : '-8px';
                            if (profile) {
                                chattersHtml += `<img src="${profile}" class="rounded-circle border border-2 border-white shadow-sm admin-chat-chatter-avatar" style="margin-left: ${margin}; z-index: ${zIndex};" onerror="this.src='${config.placeholderImage}'">`;
                            } else {
                                chattersHtml += `<span style="margin-left: ${margin}; z-index: ${zIndex}; position: relative;">${generateInitialAvatar('', 26)}</span>`;
                            }
                        });
                        
                        if (remaining > 0) {
                            chattersHtml += `<span class="ms-1 text-muted admin-chat-chatter-more">+${remaining}</span>`;
                        }
                    }

                    // Bottom row formatting Price dot Chatters
                    let priceAndChattersHtml = '';
                    if (priceDisplay && chattersHtml) {
                        priceAndChattersHtml = `<div class="d-flex align-items-center">${priceDisplay} <span class="text-muted mx-2 admin-chat-dot-separator">&#x2022;</span> <div class="d-flex align-items-center">${chattersHtml}</div></div>`;
                    } else if (chattersHtml) {
                        priceAndChattersHtml = `<div class="d-flex align-items-center">${chattersHtml}</div>`;
                    } else if (priceDisplay) {
                        priceAndChattersHtml = `<div class="d-flex align-items-center">${priceDisplay}</div>`;
                    }

                    const timeDisplay = product.last_message_time ? formatChatListTime(product.last_message_time) : '';
                    const timeHtml = timeDisplay ? `<span class="time-text">${timeDisplay}</span>` : '';

                    const productHtml = `
                        <div class="product-item py-3 px-3 border-bottom" data-product-id="${product.id}" onclick="window.adminChatSelectProduct('${product.id}')">
                            <div class="d-flex w-100 align-items-center">
                                <img src="${productImage}" alt="${escapeHtml(product.name || '')}" class="rounded-circle shadow-sm admin-chat-product-image" onerror="this.src='${config.placeholderImage}'">
                                <div class="flex-grow-1 ms-3 d-flex flex-column justify-content-center admin-chat-flex-zero">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div class="text-truncate admin-chat-product-title">${escapeHtml(product.name || '')}</div>
                                        ${timeHtml}
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        ${priceAndChattersHtml}
                                        ${unreadBadge}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    container.append(productHtml);
                });

                // Securely reapply the active state via explicit targeting
                if (selectedProductId) {
                    $(`.product-item[data-product-id="${selectedProductId}"]`).addClass('active');
                }

                tryAutoSelectFromUrl();
            }

            // Auto-select product + chat from URL query (?product_id=X&chat_id=Y)
            const _autoSelectParams = new URLSearchParams(window.location.search);
            let _pendingAutoProduct = _autoSelectParams.get('product_id');
            let _pendingAutoChat    = _autoSelectParams.get('chat_id');

            // Strip product_id/chat_id from the URL once consumed, so a manual
            // refresh doesn't replay the auto-open (which marks the chat read
            // again every time and permanently hides its unread badge).
            function cleanAutoSelectUrlParams() {
                const url = new URL(window.location.href);
                url.searchParams.delete('product_id');
                url.searchParams.delete('chat_id');
                window.history.replaceState({}, '', url.pathname + url.search + url.hash);
            }

            function tryAutoSelectFromUrl() {
                if (!_pendingAutoProduct) return;
                if (!$(`.product-item[data-product-id="${_pendingAutoProduct}"]`).length) return;
                const productId = _pendingAutoProduct;
                _pendingAutoProduct = null;
                if (!_pendingAutoChat) cleanAutoSelectUrlParams();
                window.adminChatSelectProduct(productId);
            }

            function tryAutoSelectChatFromUrl() {
                if (!_pendingAutoChat) return;
                if (!$(`.chat-item[data-chat-id="${_pendingAutoChat}"]`).length) return;
                const chatId = _pendingAutoChat;
                _pendingAutoChat = null;
                cleanAutoSelectUrlParams();
                window.adminChatSelectChat(parseInt(chatId, 10));
            }

            window.adminChatSelectProduct = function(productId) {
                selectedChatId = null;
                currentChatId = null;
                
                selectedProductId = productId;
                $('.product-item').removeClass('active');
                $(`.product-item[data-product-id="${productId}"]`).addClass('active');
                
                // Clear chat window
                $('#chat-header').removeClass('d-flex').addClass('d-none');
                $('#chat-offer-banner').removeClass('d-flex').addClass('d-none');

                $('#chat-messages').addClass('d-flex flex-column align-items-center justify-content-center');
                $('#chat-messages').html(`
                    <div class="text-center p-4">
                        <img src="${config.images.emptyMessages}" alt="${config.translations.noMessages}" class="admin-chat-msg-empty-icon">
                        <h4 class="admin-chat-msg-empty-title">${config.translations.noMessages}</h4>
                        <p class="text-muted mt-2 admin-chat-msg-empty-subtitle">${config.translations.noMessageSelectedDesc}</p>
                    </div>
                `);

                // Reset pagination
                chatsPage = 1;
                chatsHasMore = true;
                chatsLoading = false;
                loadChatList(productId, false);
            };

            function showChatsShimmer() {
                const container = $('#chats-list');
                container.empty();
                let shimmerHtml = '';
                for (let i = 0; i < 4; i++) {
                    shimmerHtml += `
                        <div class="shimmer-wrapper">
                            <div class="d-flex align-items-center">
                                <div class="shimmer-item shimmer-avatar me-2"></div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="shimmer-item shimmer-title"></div>
                                        <div class="shimmer-item shimmer-time"></div>
                                    </div>
                                    <div class="shimmer-item shimmer-text"></div>
                                </div>
                            </div>
                        </div>
                    `;
                }
                container.html(shimmerHtml);
            }

            function loadChatList(productId, append = false, search = '', callback = null) {
                if (chatsLoading) {
                    if (callback) callback();
                    return;
                }
                if (append && !chatsHasMore) {
                    if (callback) callback();
                    return;
                }
                
                chatsLoading = true;
                if (!append) {
                    showChatsShimmer();
                }
                
                const pageToLoad = append ? chatsPage + 1 : 1;
                const searchQuery = search || chatsSearch || '';
                
                $.ajax({
                    url: config.routes.chatList,
                    method: 'GET',
                    cache: false,
                    data: {
                        product_id: productId,
                        page: pageToLoad,
                        search: searchQuery
                    },
                    success: function(response) {
                        chatsLoading = false;
                        if (response.error === false) {
                            const data = response.data;
                            chatsHasMore = data.has_more || false;
                            
                            if (append) {
                                chatsPage = pageToLoad;
                                renderChats(data.data || [], true);
                            } else {
                                chatsPage = data.current_page;
                                renderChats(data.data || [], false);
                            }
                            updateChatsSummary(data);
                            
                            // Ensure scrollbar is visible if content overflows
                            setTimeout(function() {
                                const chatsList = document.getElementById('chats-list');
                                if (chatsList && chatsList.scrollHeight > chatsList.clientHeight) {
                                    chatsList.style.overflowY = 'scroll';
                                }
                            }, 100);
                        }
                        if (callback) callback();
                    },
                    error: function() {
                        chatsLoading = false;
                        if (!append) {
                            showErrorToast(config.translations.failedChats);
                        }
                        if (callback) callback();
                    }
                });
            }

            function renderChats(chats, append = false) {
                const container = $('#chats-list');
                
                if (!append) {
                    container.empty();
                }

                if (chats.length === 0 && !append) {
                    container.html(`
                        <div class="text-center p-4 mt-5">
                            <img src="${config.images.emptyChats}" alt="${config.translations.noChatFoundTitle}" class="admin-chat-empty-icon">
                            <h5 class="admin-chat-empty-title">${config.translations.noChatFoundTitle}</h5>
                            <p class="text-muted admin-chat-empty-subtitle">${config.translations.noChatFoundDesc}</p>
                        </div>
                    `);
                    return;
                }

                chats.forEach(function(chat) {
                    const otherUser = chat.other_user || {};
                    const unreadCount = chat.unread_chat_count || 0;

                    let lastMessage = chat.last_message || config.translations.noMessages;
                    // Truncate long messages
                    if (lastMessage.length > 50) {
                        lastMessage = lastMessage.substring(0, 50) + '...';
                    }
                    
                    const lastMessageTime = chat.last_message_time ? formatChatListTime(chat.last_message_time) : '';

                    const isChecked = selectedChatIds.has(chat.id);
                    const checkboxHtml = chatSelectMode ? `<input type="checkbox" class="admin-chat-select-checkbox me-2" data-chat-id="${chat.id}" ${isChecked ? 'checked' : ''} onclick="event.stopPropagation(); window.adminChatToggleChatSelection(${chat.id})">` : '';

                    const chatHtml = `
                        <div class="chat-item ${chatSelectMode ? 'select-mode' : ''}" data-chat-id="${chat.id}" onclick="window.adminChatSelectChat(${chat.id})">
                            <div class="d-flex align-items-center">
                                ${checkboxHtml}
                                ${otherUser.profile ? `<img src="${otherUser.profile}" alt="${otherUser.name || ''}" class="me-2 rounded-circle" style="width:40px;height:40px;object-fit:cover;" onerror="this.src='${config.placeholderImage}'">` : `<span class="me-2">${generateInitialAvatar(otherUser.name || '', 40)}</span>`}
                                <div class="flex-grow-1 chat-item-content admin-chat-flex-zero">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="fw-bold text-truncate admin-chat-w-120">${escapeHtml(otherUser.name || config.translations.unknownUser)}</div>
                                        <div class="d-flex flex-column align-items-end">
                                            ${lastMessageTime ? `<div class="text-primary small mb-1">${lastMessageTime}</div>` : ''}
                                            ${unreadCount > 0 ? `<span class="unread-badge">${unreadCount}</span>` : ''}
                                        </div>
                                    </div>
                                    <div class="text-muted small text-truncate">${escapeHtml(lastMessage)}</div>
                                </div>
                            </div>
                        </div>
                    `;
                    container.append(chatHtml);
                });

                // Securely reapply the active state via explicit targeting
                if (selectedChatId) {
                    $(`.chat-item[data-chat-id="${selectedChatId}"]`).addClass('active');
                }

                tryAutoSelectChatFromUrl();
            }

            function updateChatsSummary(data) {
                const chats = data.data || [];
                const total = data.total !== undefined ? data.total : chats.length;
                const unread = chats.reduce((sum, chat) => sum + (parseInt(chat.unread_chat_count) || 0), 0);
                $('#chats-summary').text(`${total} ${config.translations.message}, ${unread} ${config.translations.unread}`);
            }

            window.adminChatSelectChat = function(chatId) {
                // In selection mode, toggle checkbox instead of opening chat
                if (chatSelectMode) {
                    window.adminChatToggleChatSelection(chatId);
                    return;
                }

                // Prevent multiple calls if same chat is selected
                if (selectedChatId === chatId && currentChatId === chatId) {
                    return;
                }
                
                selectedChatId = chatId;
                currentChatId = chatId;
                clearFilePreview();
                $('#compose-link-preview').empty();
                $('.chat-item').removeClass('active');
                const chatItemEl = $(`.chat-item[data-chat-id="${chatId}"]`);
                chatItemEl.addClass('active');
                selectedChatUserName = chatItemEl.find('.fw-bold').text();
                selectedChatUserImage = chatItemEl.find('img').attr('src') || '';

                // Load messages
                loadMessages(chatId, false, function() {
                    // Refresh chat list to update unread counts
                    if (selectedProductId) {
                        setTimeout(function() {
                            loadChatList(selectedProductId, false, chatsSearch);
                        }, 300);
                    }
                    // Refresh product list as well so the unread badge is cleared
                    setTimeout(function() {
                        productsPage = 1;
                        productsHasMore = true;
                        loadProducts(productsSearch, false);
                    }, 500);
                });
            };

            function loadMessages(chatId, silent = false, callback = null) {
                if (messagesLoading) {
                    if (callback) callback();
                    return;
                }
                
                if (!silent && currentChatId !== chatId) {
                    if (callback) callback();
                    return;
                }
                
                messagesLoading = true;
                
                $.ajax({
                    url: config.routes.messages,
                    method: 'GET',
                    cache: false,
                    data: { item_offer_id: chatId },
                    timeout: 10000,
                    success: function(response) {
                        messagesLoading = false;
                        
                        if (currentChatId === chatId) {
                            if (response.error === false) {
                                currentOffer = (response.data && response.data.offer) ? response.data.offer : null;
                                const messages = response.data.data || response.data || [];
                                renderMessages(messages.reverse());
                                if (!silent) {
                                    scrollToBottom();
                                } else {
                                    const container = $('#chat-messages');
                                    const scrollTop = container.scrollTop();
                                    const scrollHeight = container[0].scrollHeight;
                                    const clientHeight = container[0].clientHeight;
                                    if (scrollHeight - scrollTop - clientHeight < 100) {
                                        scrollToBottom();
                                    }
                                }
                            }
                        }
                        if (callback) callback();
                    },
                    error: function(xhr, status, error) {
                        messagesLoading = false;
                        if (!silent && currentChatId === chatId) {
                            if (status !== 'abort') {
                                showErrorToast(config.translations.failedMessages);
                            }
                        }
                        if (callback) callback();
                    },
                    complete: function() {
                        messagesLoading = false;
                    }
                });
            }

            function renderMessages(messages) {
                const container = $('#chat-messages');
                // Keep the blocked user banner
                const blockedBanner = $('#blocked-user-banner');
                const bannerHtml = blockedBanner.length ? blockedBanner[0].outerHTML : '';
                
                container.empty();
                // Remove the empty state flex classes correctly when messages load
                container.removeClass('d-flex flex-column align-items-center justify-content-center');
                
                // Show chat header and input
                $('#chat-header').removeClass('d-none').addClass('d-flex');


                // Get chat info for header (cached at selection time — DOM sidebar
                // rows get rebuilt on refresh/visibilitychange, so re-scraping here
                // races with that rebuild and can grab stale/empty values)
                const chatItem = $(`.chat-item[data-chat-id="${selectedChatId}"]`);
                if (chatItem.length) {
                    selectedChatUserName = chatItem.find('.fw-bold').text();
                    selectedChatUserImage = chatItem.find('img').attr('src') || '';
                }
                const userName = selectedChatUserName;
                const userImage = selectedChatUserImage;
                $('#chat-user-name').text(userName);

                // Offer info in header: item name + item price
                if (currentOffer) {
                    $('#chat-item-name').text(currentOffer.item_name || '');
                    $('#chat-item-price').text(currentOffer.item_price_formatted || '');
                } else {
                    $('#chat-item-name').text('');
                    $('#chat-item-price').text('');
                }

                // Offer banner: "Make an Offer" prompt until the first offer is sent, then "Current Offer" + Edit
                hasSentOffer = messages.some(m => m.is_offer);

                // Track the amount of the last offer sent by the current (admin) user,
                // so re-sending the same amount can be blocked without affecting the buyer's counter-offers.
                lastOwnOfferAmount = null;
                for (let i = messages.length - 1; i >= 0; i--) {
                    const m = messages[i];
                    if (m.is_offer && m.sender_id == currentUserId) {
                        lastOwnOfferAmount = parseFloat(m.amount);
                        break;
                    }
                }
                if (currentOffer && !currentOffer.is_job_category) {
                    $('#chat-offer-banner').removeClass('d-none').addClass('d-flex');
                    if (hasSentOffer) {
                        $('#chat-offer-banner-title').text('{{ __('Current Offer') }}');
                        $('#chat-offer-banner-subtitle').text(currentOffer.amount_formatted || '');
                        $('#chat-offer-banner-btn').text('{{ __('Edit an Offer') }}');
                    } else {
                        $('#chat-offer-banner-title').text('{{ __('Make an Offer') }}');
                        $('#chat-offer-banner-subtitle').text('{{ __('Set your price and send an offer to the buyer.') }}');
                        $('#chat-offer-banner-btn').text('{{ __('Make an Offer') }}');
                    }
                } else {
                    $('#chat-offer-banner').removeClass('d-flex').addClass('d-none');
                }
                if (userImage) {
                    $('#chat-user-avatar-wrapper').html(`<img src="${userImage}" alt="" class="rounded-circle admin-chat-user-avatar" onerror="this.src='${config.placeholderImage}'">`);
                } else {
                    $('#chat-user-avatar-wrapper').html(generateInitialAvatar(userName || '', 40));
                }
                const safeUserImage = userImage || '';

                // Check if user is blocked (from first message's is_blocked flag)
                const isBlocked = messages.length > 0 && messages[0].is_blocked === true;
                
                // Update block/unblock menu items visibility
                if (isBlocked) {
                    $('#block-user-menu-item').hide();
                    $('#unblock-user-menu-item').show();
                } else {
                    $('#block-user-menu-item').show();
                    $('#unblock-user-menu-item').hide();
                }

                // Show/hide blocked banner and input
                if (isBlocked) {
                    $('#blocked-user-banner').show();
                    $('#chat-input-container').show();
                    // Hide the form inside chat-input-container but show banner
                    $('#chat-input-container .input-group').hide();
                    // Remove any existing inline banner and add new one
                    $('#chat-input-container .blocked-inline-banner').remove();
                    const inlineBanner = `<div class="blocked-inline-banner text-center py-2">
                        <span class="badge bg-warning text-dark py-2 px-3">
                            <i class="fas fa-ban me-1"></i>
                            {{ __('You have blocked this user') }}. 
                            <a href="javascript:void(0)" onclick="window.adminChatUnblockUser()" class="text-decoration-underline text-dark fw-bold">{{ __('Click here to unblock') }}</a>
                        </span>
                    </div>`;
                    $('#chat-input-container').prepend(inlineBanner);
                } else {
                    $('#blocked-user-banner').hide();
                    $('#chat-input-container').show();
                    $('#chat-input-container .input-group').show();
                    $('#chat-input-container .blocked-inline-banner').remove();
                }

                // Always show messages, even when blocked
                if (messages.length === 0) {
                    container.removeClass('d-flex flex-column align-items-center justify-content-center');
                    container.html(`
                        ${bannerHtml}
                        <div class="text-center p-4 w-100">
                            <img src="${config.images.emptyMessages}" alt="${config.translations.noMessages}" class="admin-chat-msg-empty-icon">
                            <h4 class="admin-chat-msg-empty-title">${config.translations.noMessages}</h4>
                            <p class="text-muted mt-2 admin-chat-msg-empty-subtitle">${config.translations.noMessageSelectedDesc}</p>
                        </div>
                    `);
                    return;
                }

                let lastRenderedDate = null;

                messages.forEach(function(message) {
                    const isSent = message.sender_id == currentUserId;
                    const messageTime = message.created_at ? formatTime(message.created_at) : '';

                    // Insert a date divider whenever the calendar date changes
                    if (message.created_at) {
                        const msgDate = getDateKey(message.created_at);
                        if (msgDate !== lastRenderedDate) {
                            lastRenderedDate = msgDate;
                            const label = formatDateLabel(message.created_at);
                            container.append(`<div class="chat-date-divider"><span>${escapeHtml(label)}</span></div>`);
                        }
                    }
                    
                    // Offers render as a centered system-style message (matches app/web), not a chat bubble
                    if (message.is_offer) {
                        const offerAmount = escapeHtml(String(message.amount_formatted || message.amount));
                        const offerLabel = isSent
                            ? `{{ __('You Offered') }} ${offerAmount}`
                            : `${escapeHtml(userName || '')} {{ __('Offered') }} ${offerAmount}`;
                        container.append(`<div class="chat-offer-msg"><span>${offerLabel}</span></div>`);
                        return;
                    }

                    let messageContent = '';
                    if (message.message) {
                        messageContent = `<div>${linkify(escapeHtml(message.message))}</div>`;
                        const previewUrl = extractFirstUrl(message.message);
                        if (previewUrl) {
                            messageContent += renderLinkPreviewPlaceholder(previewUrl);
                        }
                    }
                    if (message.file) {
                        const baseUrl = (typeof window.baseurl !== 'undefined' ? window.baseurl : window.location.origin + '/').replace(/\/$/, '');
                        const fileUrl = message.file.startsWith('http') ? message.file : baseUrl + '/' + message.file.replace(/^\//, '');
                        const mtClass = message.message ? 'mt-2' : '';
                        messageContent += `<div class="${mtClass}"><img src="${fileUrl}" alt="Image" class="admin-chat-msg-image" onclick="window.open('${fileUrl}', '_blank')" onerror="this.src='${config.placeholderImage}'"></div>`;
                    }
                    if (message.audio) {
                        const baseUrl = (typeof window.baseurl !== 'undefined' ? window.baseurl : window.location.origin + '/').replace(/\/$/, '');
                        const audioUrl = message.audio.startsWith('http') ? message.audio : baseUrl + '/' + message.audio.replace(/^\//, '');
                        const mtClass = (message.message || message.file) ? 'mt-2' : '';
                        messageContent += `<div class="${mtClass}"><audio controls><source src="${audioUrl}" type="audio/mpeg"></audio></div>`;
                    }

                    let messageHtml = '';
                    if (isSent) {
                        const userProfile = '{{ Auth::user()->profile}}';
                        const msgCheckbox = messageSelectMode ? `<input type="checkbox" class="msg-select-checkbox me-1" data-msg-id="${message.id}" ${selectedMessageIds.has(message.id) ? 'checked' : ''} onclick="event.stopPropagation(); window.adminChatToggleMessageSelection(${message.id})">` : '';
                        const deleteBtn = !messageSelectMode ? `<button class="btn btn-sm btn-link text-danger admin-chat-msg-delete-btn" onclick="window.adminChatDeleteMessage(${message.id})" title="{{ __('Delete') }}"><i class="fas fa-trash-alt"></i></button>` : '';
                        messageHtml = `
                            <div class="d-flex justify-content-end mb-3 align-items-end admin-chat-msg-row" ${messageSelectMode ? `onclick="window.adminChatToggleMessageSelection(${message.id})"` : ''}>
                                ${msgCheckbox}
                                ${deleteBtn}
                                <div class="d-flex flex-column align-items-end admin-chat-max-w-85">
                                    <div class="message-bubble message-sent">
                                        <span>${messageContent}</span>
                                    </div>
                                    <div class="message-time mt-1">${messageTime}</div>
                                </div>
                                ${userProfile ? `<img src="${userProfile}" alt="${escapeHtml(userName)}" class="rounded-circle me-2 chat-msg-avatar" onerror="this.src='${config.placeholderImage}'">` : `<span class="me-2">${generateInitialAvatar('{{ Auth::user()->name }}', 30)}</span>`}
                            </div>
                        `;
                    } else {
                        messageHtml = `
                            <div class="d-flex justify-content-start mb-3 align-items-end">
                                ${safeUserImage ? `<img src="${safeUserImage}" alt="${escapeHtml(userName)}" class="rounded-circle me-2 chat-msg-avatar" onerror="this.src='${config.placeholderImage}'">` : `<span class="me-2">${generateInitialAvatar(userName || '', 30)}</span>`}
                                
                                <div class="d-flex flex-column admin-chat-max-w-85">
                                    <div class="message-bubble message-received">
                                        <span>${messageContent}</span>
                                    </div>
                                    <div class="message-time mt-1">${messageTime}</div>
                                </div>
                            </div>
                        `;
                    }
                    container.append(messageHtml);
                });

                scrollToBottom();
                loadPendingLinkPreviews();
            }

            function extractFirstUrl(text) {
                const match = text.match(/(https?:\/\/[^\s<]+|www\.[^\s<]+)/);
                if (!match) return null;
                return match[0].startsWith('http') ? match[0] : 'https://' + match[0];
            }

            function linkPreviewPlaceholderId(url) {
                return 'lp-' + btoa(unescape(encodeURIComponent(url))).replace(/[^a-zA-Z0-9]/g, '');
            }

            function renderLinkPreviewPlaceholder(url) {
                const id = linkPreviewPlaceholderId(url);
                const cached = linkPreviewCache.get(url);
                if (cached === null) return ''; // known: no preview available for this url
                if (cached) return linkPreviewCardHtml(cached);
                return `<div class="link-preview-card link-preview-loading" id="${id}" data-preview-url="${escapeHtml(url)}"><span class="link-preview-spinner"></span><span class="link-preview-loading-text">${escapeHtml(config.translations.loadingPreview)}</span></div>`;
            }

            function linkPreviewCardHtml(preview) {
                const image = preview.image ? `<div class="link-preview-thumb" style="background-image:url('${escapeHtml(preview.image)}')"></div>` : '';
                const title = preview.title ? `<div class="link-preview-title">${escapeHtml(preview.title)}</div>` : '';
                const description = preview.description ? `<div class="link-preview-desc">${escapeHtml(preview.description)}</div>` : '';
                const siteName = preview.site_name ? `<div class="link-preview-site"><i class="fas fa-globe me-1"></i>${escapeHtml(preview.site_name)}</div>` : '';
                return `
                    <a href="${escapeHtml(preview.url)}" target="_blank" rel="noopener noreferrer" class="link-preview-card">
                        ${image}
                        <div class="link-preview-body">
                            ${siteName}
                            ${title}
                            ${description}
                        </div>
                    </a>
                `;
            }

            function updateComposeLinkPreview(text) {
                const container = $('#compose-link-preview');
                const fileSelected = $('#file-input')[0].files.length > 0;

                // An attached image takes priority over a link preview in the composer.
                if (fileSelected) {
                    container.empty();
                    return;
                }

                const url = text ? extractFirstUrl(text) : null;
                if (!url) {
                    container.empty();
                    return;
                }

                if (container.find(`[data-preview-url="${escapeHtml(url)}"]`).length) {
                    return; // already showing this url
                }

                const cached = linkPreviewCache.get(url);
                if (cached) {
                    container.html(composeLinkPreviewCardHtml(cached, url));
                } else if (cached === null) {
                    container.empty();
                } else {
                    container.html(`<div class="link-preview-loading" data-preview-url="${escapeHtml(url)}"><span class="link-preview-spinner"></span><span class="link-preview-loading-text">${escapeHtml(config.translations.loadingPreview)}</span></div>`);
                    fetchLinkPreview(url, function(data) {
                        // Only replace if the compose box is still showing this same url
                        // (user may have edited/cleared the text while the request was in flight).
                        const currentUrl = extractFirstUrl($('#message-input').val() || '');
                        if (currentUrl !== url || $('#file-input')[0].files.length > 0) return;
                        if (data) {
                            container.html(composeLinkPreviewCardHtml(data, url));
                        } else {
                            container.empty();
                        }
                    });
                }
            }

            function composeLinkPreviewCardHtml(preview, url) {
                const image = preview.image ? `<div class="compose-link-preview-thumb" style="background-image:url('${escapeHtml(preview.image)}')"></div>` : '';
                const title = preview.title ? `<div class="compose-link-preview-title">${escapeHtml(preview.title)}</div>` : '';
                const description = preview.description ? `<div class="compose-link-preview-desc">${escapeHtml(preview.description)}</div>` : '';
                const siteName = preview.site_name ? `<div class="compose-link-preview-site"><i class="fas fa-globe me-1"></i>${escapeHtml(preview.site_name)}</div>` : '';
                return `
                    <div class="compose-link-preview-card" data-preview-url="${escapeHtml(url)}">
                        ${image}
                        <div class="compose-link-preview-body">
                            ${siteName}
                            ${title}
                            ${description}
                        </div>
                        <button type="button" class="compose-link-preview-remove-btn" id="compose-link-preview-remove" title="{{ __('Remove preview') }}">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
            }

            function fetchLinkPreview(url, callback) {
                if (linkPreviewCache.has(url)) {
                    callback(linkPreviewCache.get(url));
                    return;
                }
                if (linkPreviewInFlight.has(url)) {
                    // Another fetch for this url is already running (e.g. from a rendered
                    // message); poll the cache briefly instead of firing a duplicate request.
                    const check = setInterval(function() {
                        if (!linkPreviewInFlight.has(url)) {
                            clearInterval(check);
                            callback(linkPreviewCache.get(url) || null);
                        }
                    }, 300);
                    return;
                }

                linkPreviewInFlight.add(url);
                $.ajax({
                    url: config.routes.linkPreview,
                    method: 'GET',
                    data: { url: url },
                    success: function(response) {
                        linkPreviewInFlight.delete(url);
                        if (response.error === false && response.data) {
                            linkPreviewCache.set(url, response.data);
                            callback(response.data);
                        } else {
                            linkPreviewCache.set(url, null);
                            callback(null);
                        }
                    },
                    error: function() {
                        linkPreviewInFlight.delete(url);
                        linkPreviewCache.set(url, null);
                        callback(null);
                    }
                });
            }

            function loadPendingLinkPreviews() {
                $('.link-preview-loading[data-preview-url]').each(function() {
                    const el = $(this);
                    const url = el.data('preview-url');
                    if (!url) return;

                    if (linkPreviewCache.has(url)) {
                        const cached = linkPreviewCache.get(url);
                        if (cached) {
                            el.replaceWith(linkPreviewCardHtml(cached));
                        } else {
                            el.remove();
                        }
                        return;
                    }

                    // Skip if a fetch for this url is already in flight (duplicate placeholders in this render).
                    if (linkPreviewInFlight.has(url)) return;
                    linkPreviewInFlight.add(url);

                    $.ajax({
                        url: config.routes.linkPreview,
                        method: 'GET',
                        data: { url: url },
                        success: function(response) {
                            linkPreviewInFlight.delete(url);
                            if (response.error === false && response.data) {
                                linkPreviewCache.set(url, response.data);
                                $(`.link-preview-card[data-preview-url="${escapeHtml(url).replace(/"/g, '\\"')}"]`).each(function() {
                                    $(this).replaceWith(linkPreviewCardHtml(response.data));
                                });
                            } else {
                                linkPreviewCache.set(url, null);
                                $(`.link-preview-card[data-preview-url="${escapeHtml(url).replace(/"/g, '\\"')}"]`).remove();
                            }
                        },
                        error: function() {
                            linkPreviewInFlight.delete(url);
                            linkPreviewCache.set(url, null);
                            $(`.link-preview-card[data-preview-url="${escapeHtml(url).replace(/"/g, '\\"')}"]`).remove();
                        }
                    });
                });
            }

            function formatFileSize(bytes) {
                if (!bytes || bytes === 0) return '0 B';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
            }

            function showFilePreview(file) {
                const bar = $('#file-preview-bar');
                const thumb = $('#file-preview-thumb');
                const nameEl = $('#file-preview-name');
                const sizeEl = $('#file-preview-size');
                const typeEl = $('#file-preview-type');

                nameEl.text(file.name);
                sizeEl.text(formatFileSize(file.size));

                const ext = (file.name.split('.').pop() || 'JPG').toUpperCase();
                typeEl.text(ext);

                const reader = new FileReader();
                reader.onload = function(e) {
                    thumb.attr('src', e.target.result).css('display', 'block').show();
                };
                reader.readAsDataURL(file);

                bar.css('display', 'flex');

                // Image attachment takes priority over a link preview in the composer.
                $('#compose-link-preview').empty();
            }

            function clearFilePreview() {
                $('#file-preview-bar').hide();
                $('#file-preview-thumb').attr('src', '').hide();
                $('#file-preview-name').text('');
                $('#file-preview-size').text('');
                $('#file-preview-type').text('');
                $('#file-input').val('');

                // Re-show a link preview for the current text now that no file blocks it.
                updateComposeLinkPreview($('#message-input').val());
            }

            function setSendButtonLoading(isLoading) {
                const sendBtn = $('#send-btn');
                if (isLoading) {
                    sendBtn.prop('disabled', true).data('original-html', sendBtn.html());
                    sendBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
                } else {
                    sendBtn.prop('disabled', false);
                    const original = sendBtn.data('original-html');
                    if (original) sendBtn.html(original);
                }
            }

            function sendMessage() {
                if (!selectedChatId) {
                    showErrorToast(config.translations.selectChatFirst);
                    return;
                }

                const messageText = $('#message-input').val().trim();
                const fileInput = $('#file-input')[0];
                const hasFile = fileInput.files.length > 0;

                if (!messageText && !hasFile) {
                    return; // Nothing to send
                }

                const formData = new FormData();
                formData.append('item_offer_id', selectedChatId);
                formData.append('message', $('#message-input').val());
                
                if (hasFile) {
                    formData.append('file', fileInput.files[0]);
                }

                setSendButtonLoading(true);

                $.ajax({
                    url: config.routes.sendMessage,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        setSendButtonLoading(false);
                        if (response.error === false) {
                            $('#message-input').val('').css('height', 'auto');
                            clearFilePreview();
                            $('#compose-link-preview').empty();
                            if (!messagesLoading) {
                                loadMessages(selectedChatId, true);
                            }
                            if (selectedProductId) {
                                loadChatList(selectedProductId, false, chatsSearch);
                            }
                            // Refresh product list so the item moves to first position
                            productsPage = 1;
                            productsHasMore = true;
                            loadProducts(productsSearch, false);
                        }else{
                            showErrorToast(response.message || config.translations.failedSend);
                        }
                    },
                    error: function(xhr) {
                        setSendButtonLoading(false);
                        const error = xhr.responseJSON?.message || config.translations.failedSend;
                        showErrorToast(error);
                    }
                });
            }

            /**
             * Returns "HH:MM AM/PM" — used inside the chat message bubble.
             */
            function formatTime(dateString) {
                const date = new Date(dateString);
                const hours = date.getHours();
                const minutes = date.getMinutes();
                const ampm = hours >= 12 ? 'PM' : 'AM';
                const displayHours = hours % 12 || 12;
                const displayMinutes = minutes < 10 ? '0' + minutes : minutes;
                return `${displayHours}:${displayMinutes} ${ampm}`;
            }

            /**
             * Returns "YYYY-MM-DD" key for the local calendar date — used to detect day changes.
             */
            function getDateKey(dateString) {
                const d = new Date(dateString);
                return `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`;
            }

            /**
             * Returns a human-friendly date label for a date divider:
             * "Today", "Yesterday", or "Mon DD, YYYY".
             */
            function formatDateLabel(dateString) {
                const d = new Date(dateString);
                const now = new Date();
                const todayKey = `${now.getFullYear()}-${now.getMonth()}-${now.getDate()}`;
                const yesterdayKey = (() => {
                    const y = new Date(now); y.setDate(now.getDate() - 1);
                    return `${y.getFullYear()}-${y.getMonth()}-${y.getDate()}`;
                })();
                const msgKey = `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`;
                if (msgKey === todayKey) return '{{ __('Today') }}';
                if (msgKey === yesterdayKey) return '{{ __('Yesterday') }}';
                return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
            }

            /**
             * Returns a smart timestamp for the chat list:
             * Today → "HH:MM AM/PM", Yesterday → "Yesterday", older → short date.
             */
            function formatChatListTime(dateString) {
                const d = new Date(dateString);
                const now = new Date();
                const todayKey = `${now.getFullYear()}-${now.getMonth()}-${now.getDate()}`;
                const yesterdayKey = (() => {
                    const y = new Date(now); y.setDate(now.getDate() - 1);
                    return `${y.getFullYear()}-${y.getMonth()}-${y.getDate()}`;
                })();
                const msgKey = `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`;
                if (msgKey === todayKey) return formatTime(dateString);
                if (msgKey === yesterdayKey) return '{{ __('Yesterday') }}';
                return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
            }

            function scrollToBottom() {
                const container = $('#chat-messages');
                setTimeout(() => {
                    container.scrollTop(container[0].scrollHeight);
                }, 100);
            }

            function refreshOfferSubmitState() {
                const amount = parseFloat($('#offer-amount-input').val());
                const invalid = !amount || amount <= 0;
                const unchanged = lastOwnOfferAmount !== null && amount === lastOwnOfferAmount;
                $('#offer-submit-btn').prop('disabled', invalid || unchanged);
            }

            window.adminChatOpenOfferModal = function() {
                if (!currentOffer || !selectedChatId) return;

                $('#offer-modal-title').html(hasSentOffer ?
                    `{{ __('Edit an') }} <span class="text-primary">{{ __('Offer') }}</span>` :
                    `{{ __('Make an') }} <span class="text-primary">{{ __('Offer') }}</span>`);
                $('#offer-submit-btn').text(hasSentOffer ? '{{ __('Update Offer') }}' : '{{ __('Send Offer') }}');
                $('#offer-modal-seller-price').text(currentOffer.item_price_formatted || '');
                $('#offer-amount-input').val(hasSentOffer ? currentOffer.amount : '');
                refreshOfferSubmitState();

                const modalEl = document.getElementById('offer-modal');
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            };

            $('#offer-amount-input').on('input', refreshOfferSubmitState);

            function submitOffer() {
                if (!selectedChatId) return;

                const amount = parseFloat($('#offer-amount-input').val());
                if (!amount || amount <= 0) return;

                const submitBtn = $('#offer-submit-btn');
                submitBtn.prop('disabled', true);

                $.ajax({
                    url: config.routes.sendOffer,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        item_offer_id: selectedChatId,
                        amount: amount,
                    },
                    success: function(response) {
                        if (response.error === false) {
                            bootstrap.Modal.getOrCreateInstance(document.getElementById('offer-modal')).hide();
                            loadMessages(selectedChatId, false);
                        } else {
                            showErrorToast(response.message || config.translations.failedMessages);
                        }
                    },
                    error: function(xhr) {
                        showErrorToast((xhr.responseJSON && xhr.responseJSON.message) || config.translations.failedMessages);
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                    }
                });
            }

            window.adminChatDeleteChat = function() {
                if (!selectedChatId) return;

                if (!confirm(config.translations.confirmDeleteChat)) return;

                $.ajax({
                    url: config.routes.deleteChat,
                    method: 'POST',
                    data: { item_offer_id: selectedChatId },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.error === false) {
                            showSuccessToast(config.translations.chatDeleted);
                            selectedChatId = null;
                            currentChatId = null;

                            // Reset chat window
                            $('#chat-header').removeClass('d-flex').addClass('d-none');
                $('#chat-offer-banner').removeClass('d-flex').addClass('d-none');
                            $('#chat-messages').addClass('d-flex flex-column align-items-center justify-content-center');
                            $('#chat-messages').html(`
                                <div class="text-center p-4">
                                    <img src="${config.images.emptyMessages}" alt="${config.translations.noMessageSelectedTitle}" class="admin-chat-msg-empty-icon">
                                    <h4 class="admin-chat-msg-empty-title">${config.translations.noMessageSelectedTitle}</h4>
                                    <p class="text-muted mt-2 admin-chat-msg-empty-subtitle">${config.translations.noMessageSelectedDesc}</p>
                                </div>
                            `);

                            // Reload chat list and products
                            if (selectedProductId) {
                                chatsPage = 1;
                                chatsHasMore = true;
                                chatsLoading = false;
                                loadChatList(selectedProductId, false, chatsSearch);
                            }
                            productsPage = 1;
                            productsHasMore = true;
                            loadProducts(productsSearch, false);
                        } else {
                            showErrorToast(response.message || config.translations.failedDeleteChat);
                        }
                    },
                    error: function(xhr) {
                        const error = xhr.responseJSON?.message || config.translations.failedDeleteChat;
                        showErrorToast(error);
                    }
                });
            };

            window.adminChatDeleteMessage = function(messageId) {
                if (!selectedChatId || !messageId) return;

                if (!confirm(config.translations.confirmDeleteMessage)) return;

                $.ajax({
                    url: config.routes.deleteMessages,
                    method: 'POST',
                    data: {
                        item_offer_id: selectedChatId,
                        message_ids: [messageId]
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.error === false) {
                            showSuccessToast(config.translations.messageDeleted);
                            // Force reload messages
                            messagesLoading = false;
                            loadMessages(selectedChatId, false);
                            // Also refresh chat list to update last message preview
                            if (selectedProductId) {
                                loadChatList(selectedProductId, false, chatsSearch);
                            }
                        } else {
                            showErrorToast(response.message || config.translations.failedDeleteMessage);
                        }
                    },
                    error: function(xhr) {
                        const error = xhr.responseJSON?.message || config.translations.failedDeleteMessage;
                        showErrorToast(error);
                    }
                });
            };

            // ===== Multi Chat Selection =====
            window.adminChatToggleSelectMode = function() {
                chatSelectMode = !chatSelectMode;
                selectedChatIds.clear();
                updateChatSelectUI();
                // Re-render chats to show/hide checkboxes
                if (selectedProductId) {
                    chatsPage = 1;
                    chatsHasMore = true;
                    loadChatList(selectedProductId, false, chatsSearch);
                }
            };

            window.adminChatToggleChatSelection = function(chatId) {
                if (selectedChatIds.has(chatId)) {
                    selectedChatIds.delete(chatId);
                } else {
                    selectedChatIds.add(chatId);
                }
                // Update checkbox state
                const checkbox = $(`.admin-chat-select-checkbox[data-chat-id="${chatId}"]`);
                checkbox.prop('checked', selectedChatIds.has(chatId));
                updateChatSelectUI();
            };

            function updateChatSelectUI() {
                const count = selectedChatIds.size;
                $('#chat-selected-count').text(count);
                $('#chat-delete-selected-btn').prop('disabled', count === 0);
                if (chatSelectMode) {
                    $('#chat-select-actions').addClass('active');
                    $('#chat-select-toggle-btn').addClass('text-primary').removeClass('text-muted');
                } else {
                    $('#chat-select-actions').removeClass('active');
                    $('#chat-select-toggle-btn').removeClass('text-primary').addClass('text-muted');
                }
            }

            window.adminChatDeleteSelectedChats = function() {
                if (selectedChatIds.size === 0) {
                    showErrorToast(config.translations.noChatsSelected);
                    return;
                }

                if (!confirm(config.translations.confirmDeleteSelectedChats)) return;

                const ids = Array.from(selectedChatIds);

                $.ajax({
                    url: config.routes.deleteChat,
                    method: 'POST',
                    data: { item_offer_id: ids },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.error === false) {
                            showSuccessToast(config.translations.chatDeleted);

                            // If current open chat was among deleted, reset chat window
                            if (selectedChatId && selectedChatIds.has(selectedChatId)) {
                                selectedChatId = null;
                                currentChatId = null;
                                $('#chat-header').removeClass('d-flex').addClass('d-none');
                $('#chat-offer-banner').removeClass('d-flex').addClass('d-none');
                                $('#chat-messages').addClass('d-flex flex-column align-items-center justify-content-center');
                                $('#chat-messages').html(`
                                    <div class="text-center p-4">
                                        <img src="${config.images.emptyMessages}" alt="${config.translations.noMessageSelectedTitle}" class="admin-chat-msg-empty-icon">
                                        <h4 class="admin-chat-msg-empty-title">${config.translations.noMessageSelectedTitle}</h4>
                                        <p class="text-muted mt-2 admin-chat-msg-empty-subtitle">${config.translations.noMessageSelectedDesc}</p>
                                    </div>
                                `);
                            }

                            // Exit select mode and reload
                            chatSelectMode = false;
                            selectedChatIds.clear();
                            updateChatSelectUI();

                            if (selectedProductId) {
                                chatsPage = 1;
                                chatsHasMore = true;
                                chatsLoading = false;
                                loadChatList(selectedProductId, false, chatsSearch);
                            }
                            productsPage = 1;
                            productsHasMore = true;
                            loadProducts(productsSearch, false);
                        } else {
                            showErrorToast(response.message || config.translations.failedDeleteChat);
                        }
                    },
                    error: function(xhr) {
                        const error = xhr.responseJSON?.message || config.translations.failedDeleteChat;
                        showErrorToast(error);
                    }
                });
            };

            // ===== Multi Message Selection =====
            window.adminChatToggleMessageSelectMode = function() {
                messageSelectMode = !messageSelectMode;
                selectedMessageIds.clear();
                updateMessageSelectUI();
                // Re-render messages to show/hide checkboxes
                if (selectedChatId) {
                    messagesLoading = false;
                    loadMessages(selectedChatId, false);
                }
            };

            window.adminChatToggleMessageSelection = function(msgId) {
                if (selectedMessageIds.has(msgId)) {
                    selectedMessageIds.delete(msgId);
                } else {
                    selectedMessageIds.add(msgId);
                }
                // Update checkbox state
                const checkbox = $(`.msg-select-checkbox[data-msg-id="${msgId}"]`);
                checkbox.prop('checked', selectedMessageIds.has(msgId));
                updateMessageSelectUI();
            };

            function updateMessageSelectUI() {
                const count = selectedMessageIds.size;
                $('#msg-selected-count').text(count);
                $('#msg-delete-selected-btn').prop('disabled', count === 0);
                if (messageSelectMode) {
                    $('#msg-select-actions').addClass('active');
                    $('#chat-input-container').hide();
                } else {
                    $('#msg-select-actions').removeClass('active');
                    $('#chat-input-container').show();
                }
            }

            window.adminChatDeleteSelectedMessages = function() {
                if (selectedMessageIds.size === 0) {
                    showErrorToast(config.translations.noMessagesSelected);
                    return;
                }

                if (!confirm(config.translations.confirmDeleteSelectedMessages)) return;

                const ids = Array.from(selectedMessageIds);

                $.ajax({
                    url: config.routes.deleteMessages,
                    method: 'POST',
                    data: {
                        item_offer_id: selectedChatId,
                        message_ids: ids
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.error === false) {
                            showSuccessToast(config.translations.messageDeleted);

                            // Exit select mode and reload
                            messageSelectMode = false;
                            selectedMessageIds.clear();
                            updateMessageSelectUI();

                            messagesLoading = false;
                            loadMessages(selectedChatId, false);
                            if (selectedProductId) {
                                loadChatList(selectedProductId, false, chatsSearch);
                            }
                        } else {
                            showErrorToast(response.message || config.translations.failedDeleteMessage);
                        }
                    },
                    error: function(xhr) {
                        const error = xhr.responseJSON?.message || config.translations.failedDeleteMessage;
                        showErrorToast(error);
                    }
                });
            };

            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, m => map[m]);
            }

            function linkify(escapedText) {
                return escapedText.replace(/(https?:\/\/[^\s<]+|www\.[^\s<]+)/g, function(url) {
                    const href = url.startsWith('http') ? url : 'https://' + url;
                    return `<a href="${href}" target="_blank" rel="noopener noreferrer">${url}</a>`;
                });
            }

            // ========== Block/Unblock Functions ==========
            window.adminChatBlockUser = function() {
                if (!selectedChatId) {
                    showErrorToast(config.translations.selectChatFirst);
                    return;
                }

                Swal.fire({
                    title: '{{ __("Block User") }}',
                    text: '{{ __("Are you sure you want to block this user? You won\'t be able to send messages until you unblock them.") }}',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '{{ __("Block") }}',
                    cancelButtonText: '{{ __("Cancel") }}',
                    confirmButtonColor: '#f39c12',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: config.routes.blockUser,
                            method: 'POST',
                            data: { item_offer_id: selectedChatId },
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.error === false) {
                                    showSuccessToast(response.message || '{{ __("User blocked successfully") }}');
                                    loadMessages(selectedChatId, false);
                                } else {
                                    showErrorToast(response.message || '{{ __("Failed to block user") }}');
                                }
                            },
                            error: function(xhr) {
                                const error = xhr.responseJSON?.message || '{{ __("Failed to block user") }}';
                                showErrorToast(error);
                            }
                        });
                    }
                });
            };

            window.adminChatUnblockUser = function() {
                if (!selectedChatId) {
                    showErrorToast(config.translations.selectChatFirst);
                    return;
                }

                Swal.fire({
                    title: '{{ __("Unblock User") }}',
                    text: '{{ __("Are you sure you want to unblock this user? You will be able to send messages again.") }}',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '{{ __("Unblock") }}',
                    cancelButtonText: '{{ __("Cancel") }}',
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: config.routes.unblockUser,
                            method: 'POST',
                            data: { item_offer_id: selectedChatId },
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.error === false) {
                                    showSuccessToast(response.message || '{{ __("User unblocked successfully") }}');
                                    loadMessages(selectedChatId, false);
                                } else {
                                    showErrorToast(response.message || '{{ __("Failed to unblock user") }}');
                                }
                            },
                            error: function(xhr) {
                                const error = xhr.responseJSON?.message || '{{ __("Failed to unblock user") }}';
                                showErrorToast(error);
                            }
                        });
                    }
                });
            };

            // ========== Audio Recording Variables ==========
            let audioRecorder = null;
            let recordedAudioBlob = null;
            let recordingTimerInterval = null;
            let recordingSeconds = 0;
            let isRecordingCancelled = false;
            let isPaused = false;
            const MAX_RECORDING_SECONDS = 120;

            // ========== Audio Recording Functions ==========
            $('#record-audio-btn').on('click', function() {
                startRecording();
            });

            $('#stop-recording-btn').on('click', function() {
                stopRecording();
            });

            $('#pause-recording-btn').on('click', function() {
                togglePauseRecording();
            });

            $('#cancel-recording-btn').on('click', function() {
                cancelRecording();
            });

            $('#delete-recorded-audio-btn').on('click', function() {
                cancelRecording();
            });

            $('#send-recorded-audio-btn').on('click', function() {
                sendRecordedAudio();
            });

            async function startRecording() {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    audioRecorder = new MediaRecorder(stream);
                    recordedAudioBlob = null;
                    recordingSeconds = 0;
                    isRecordingCancelled = false;

                    // Collect audio data
                    audioRecorder.ondataavailable = function(event) {
                        if (event.data.size > 0) {
                            recordedAudioBlob = event.data;
                        }
                    };

                    // Show recording UI, hide message input
                    $('#record-audio-btn').hide();
                    $('#message-input').hide();
                    $('#attach-file-btn').hide();
                    $('#send-btn').hide();
                    $('#recording-ui').removeClass('d-none').addClass('d-flex');
                    $('#audio-preview-ui').removeClass('d-flex').addClass('d-none');

                    // Start timer
                    recordingTimerInterval = setInterval(function() {
                        if (!isPaused) {
                            recordingSeconds++;
                            const minutes = Math.floor(recordingSeconds / 60);
                            const seconds = recordingSeconds % 60;
                            $('#recording-timer').text(minutes + ':' + (seconds < 10 ? '0' : '') + seconds);

                            // Auto-stop at max duration
                            if (recordingSeconds >= MAX_RECORDING_SECONDS) {
                                stopRecording();
                            }
                        }
                    }, 1000);

                    audioRecorder.start();

                    // Handle stop event
                    audioRecorder.onstop = function() {
                        clearInterval(recordingTimerInterval);
                        stream.getTracks().forEach(track => track.stop());

                        // Only show preview if not cancelled
                        if (!isRecordingCancelled && recordedAudioBlob) {
                            showAudioPreview();
                        }
                    };

                } catch (err) {
                    console.error('Error accessing microphone:', err);
                    showErrorToast('{{ __("Could not access microphone. Please ensure microphone permission is granted.") }}');
                }
            }

            function stopRecording() {
                if (audioRecorder && audioRecorder.state !== 'inactive') {
                    audioRecorder.stop();
                }
                clearInterval(recordingTimerInterval);
            }

            function togglePauseRecording() {
                if (!audioRecorder) return;

                if (isPaused) {
                    audioRecorder.resume();
                    $('#pause-recording-btn').html('<i class="fas fa-pause"></i>').attr('title', '{{ __("Pause") }}');
                    isPaused = false;
                } else {
                    audioRecorder.pause();
                    $('#pause-recording-btn').html('<i class="fas fa-play"></i>').attr('title', '{{ __("Resume") }}');
                    isPaused = true;
                }
            }

            function cancelRecording() {
                isRecordingCancelled = true;
                if (audioRecorder && audioRecorder.state !== 'inactive') {
                    audioRecorder.stop();
                }
                clearInterval(recordingTimerInterval);
                recordedAudioBlob = null;
                resetRecordingUI();
            }

            function showAudioPreview() {
                const audioUrl = URL.createObjectURL(recordedAudioBlob);
                $('#recorded-audio-preview').attr('src', audioUrl);
                $('#recording-ui').removeClass('d-flex').addClass('d-none');
                $('#audio-preview-ui').removeClass('d-none').addClass('d-flex');
            }

            function resetRecordingUI() {
                $('#record-audio-btn').show();
                $('#message-input').show();
                $('#attach-file-btn').show();
                $('#send-btn').show();
                $('#recording-ui').removeClass('d-flex').addClass('d-none');
                $('#audio-preview-ui').removeClass('d-flex').addClass('d-none');
                $('#recording-timer').text('0:00');
                $('#pause-recording-btn').html('<i class="fas fa-pause"></i>').attr('title', '{{ __("Pause") }}');
                recordingSeconds = 0;
                isPaused = false;
            }

            function sendRecordedAudio() {
                if (!recordedAudioBlob || !selectedChatId) {
                    return;
                }

                // Create FormData
                const formData = new FormData();
                formData.append('item_offer_id', selectedChatId);
                formData.append('audio', recordedAudioBlob, 'recording.webm');
                formData.append('message', '');

                // Show loading state
                const sendBtn = $('#send-recorded-audio-btn');
                sendBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: config.routes.sendMessage,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.error === false) {
                            recordedAudioBlob = null;
                            resetRecordingUI();
                            loadMessages(selectedChatId, false);
                            if (selectedProductId) {
                                loadChatList(selectedProductId, false, chatsSearch);
                            }
                        } else {
                            showErrorToast(response.message || config.translations.failedSend);
                            sendBtn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i>');
                        }
                    },
                    error: function(xhr) {
                        const error = xhr.responseJSON?.message || config.translations.failedSend;
                        showErrorToast(error);
                        sendBtn.prop('disabled', false).html('<i class="fas fa-paper-plane"></i>');
                    }
                });
            }

        })();
    </script>
@endsection
