<div id="sidebar" class="active">
    <div class="sidebar-wrapper active">
        <div class="sidebar-header position-relative">
            <div class="d-flex align-items-center justify-content-between">
                <div class="logo">
                    <a href="{{ url('home') }}">
                        <img src="{{ $company_logo ?? '' }}"
                            data-custom-image="{{ url('assets/images/logo/sidebar_logo.png') }}" alt="Logo"
                            srcset="">
                    </a>
                </div>
                <a href="#" class="burger-btn burger-toggle sidebar-burger-btn d-block ms-2">
                    <i class="bi bi-justify fs-3"></i>
                </a>
            </div>
        </div>

        <div class="sidebar-menu">
            <ul class="menu" id="sidebarMenu">
                <li class="sidebar-item">
                    <a href="{{ url('home') }}" class='sidebar-link'>
                        <i class="ph ph-house"></i>
                        <span class="menu-item">{{ __('Dashboard') }}</span>
                    </a>
                </li>
                @canany(['advertisement-list', 'advertisement-create', 'advertisement-update', 'advertisement-delete','category-list', 'category-create', 'category-update', 'category-delete', 'custom-field-list',
                    'custom-field-create', 'custom-field-update', 'custom-field-delete','feature-section-list', 'feature-section-create', 'feature-section-update',
                    'feature-section-delete','slider-list', 'slider-create', 'slider-update', 'slider-delete', 'home-screen-section-list', 'home-screen-section-update',
                    'tip-list', 'tip-create', 'tip-update', 'tip-delete',
                    'banner-ad-list', 'banner-ad-create', 'banner-ad-update', 'banner-ad-delete'])
                    <li class="sidebar-item has-sub">
                        <a href="#" class='sidebar-link'>
                            <i class="ph ph-cards"></i>
                            <span class="menu-item">{{ __('Ads Listing') }}</span>
                        </a>
                        <ul class="submenu" style="padding-inline-start: 0rem">
                           @canany(['advertisement-list', 'advertisement-create', 'advertisement-update', 'advertisement-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('advertisement.index') }}">{{ __('All Ads') }}</a>
                                </li>
                            @endcanany
                            @canany(['category-list', 'category-create', 'category-update', 'category-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('category.index') }}">{{ __('Categories') }}</a>
                                </li>
                            @endcanany
                            @canany(['custom-field-list', 'custom-field-create', 'custom-field-update', 'custom-field-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('custom-fields.index') }}">{{ __('Custom Fields') }}</a>
                                </li>
                            @endcanany
                        </ul>
                    </li>
                @endcanany


                @canany(['advertisement-list', 'advertisement-create', 'advertisement-update', 'advertisement-delete','category-list', 'category-create', 'category-update', 'category-delete', 'custom-field-list',
                    'custom-field-create', 'custom-field-update', 'custom-field-delete','feature-section-list', 'feature-section-create', 'feature-section-update',
                    'feature-section-delete','slider-list', 'slider-create', 'slider-update', 'slider-delete', 'home-screen-section-list', 'home-screen-section-update',
                    'tip-list', 'tip-create', 'tip-update', 'tip-delete',
                    'banner-ad-list', 'banner-ad-create', 'banner-ad-update', 'banner-ad-delete'])
                    <li class="sidebar-item has-sub">
                        <a href="#" class='sidebar-link'>
                            <i class="ph ph-layout"></i>
                            <span class="menu-item">{{ __('Content Management') }}</span>
                        </a>
                        <ul class="submenu" style="padding-inline-start: 0rem">
                            @canany(['slider-list', 'slider-create', 'slider-update', 'slider-delete'])
                                <li class="submenu-item">
                                    <a href="{{ url('slider') }}">{{ __('Slider') }}</a>
                                </li>
                            @endcanany
                            @canany(['home-screen-section-list', 'home-screen-section-update'])
                                <li class="submenu-item">
                                    <a href="{{ route('home-screen-section.index') }}">{{ __('Home Screen') }}</a>
                                </li>
                            @endcanany
                            @canany(['feature-section-list', 'feature-section-create', 'feature-section-update',
                                'feature-section-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('feature-section.index') }}">{{ __('Feature Section') }}</a>
                                </li>
                            @endcanany
                            @canany(['tip-list', 'tip-create', 'tip-update', 'tip-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('tips.index') }}">{{ __('Offer Item Tips') }}</a>
                                </li>
                            @endcanany
                            @canany(['banner-ad-list', 'banner-ad-create', 'banner-ad-update', 'banner-ad-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('banner-ad.index') }}">{{ __('Banner Ads') }}</a>
                                </li>
                            @endcanany
                        </ul>
                    </li>
                @endcanany

                @canany(['admin-chat-manage', 'chat-template-list', 'chat-template-create', 'chat-template-update', 'chat-template-delete'])
                    <li class="sidebar-item has-sub">
                        <a href="#" class='sidebar-link'>
                            <i class="ph ph-chats-circle"></i>
                            <span class="menu-item">{{ __('Chat Management') }}</span>
                        </a>
                        <ul class="submenu" style="padding-inline-start: 0rem">
                            @can('admin-chat-manage')
                                <li class="submenu-item">
                                    <a href="{{ route('admin-chat.index') }}">{{ __('Chats') }}</a>
                                </li>
                            @endcan
                            @canany(['chat-template-list', 'chat-template-create', 'chat-template-update', 'chat-template-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('chat-templates.index') }}">{{ __('Chat Templates') }}</a>
                                </li>
                            @endcanany
                        </ul>
                    </li>
                @endcanany

                @canany(['advertisement-listing-package-list', 'advertisement-listing-package-create',
                    'advertisement-listing-package-update', 'advertisement-listing-package-delete',
                    'featured-advertisement-package-list', 'featured-advertisement-package-create',
                    'featured-advertisement-package-update', 'featured-advertisement-package-delete', 'user-package-list',
                    'payment-transactions-list'])
                    <li class="sidebar-item has-sub">
                        <a href="#" class='sidebar-link'>
                            <i class="ph ph-credit-card"></i>
                            <span class="menu-item">{{ __('Package Management') }}</span>
                        </a>    
                        <ul class="submenu" style="padding-inline-start: 0rem">
                            @canany(['advertisement-listing-package-list', 'advertisement-listing-package-create',
                                'advertisement-listing-package-update', 'advertisement-listing-package-delete',
                                'featured-advertisement-package-list', 'featured-advertisement-package-create',
                                'featured-advertisement-package-update', 'featured-advertisement-package-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('package.index') }}">{{ __('Subscription Packages') }}</a>
                                </li>
                            @endcanany
                            @can('user-package-list')
                                <li class="submenu-item">
                                    <a href="{{ route('package.users.index') }}">{{ __('User Subscriptions') }}</a>
                                </li>
                            @endcan
                            @can('payment-transactions-list')
                                <li class="submenu-item">
                                    <a href="{{ route('package.payment-transactions.index') }}">{{ __('Payment Transactions') }}</a>
                                </li>
                            @endcan
                            @can('payment-transactions-list')
                                <li class="submenu-item">
                                    <a href="{{ route('package.bank-transfer.index') }}">{{ __('Bank Transfer') }}</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['campaign-list', 'campaign-create', 'campaign-update', 'campaign-delete',
                    'promotion-list', 'promotion-create', 'promotion-update', 'promotion-delete',
                    'promotion-item-list', 'promotion-item-update', 'promotion-item-delete',
                    'ad-promotion-list', 'ad-promotion-update', 'ad-promotion-delete'])
                    <li class="sidebar-item has-sub">
                        <a href="#" class='sidebar-link'>
                            <i class="ph ph-percent"></i>
                            <span class="menu-item">{{ __('Promotions & Campaigns') }}</span>
                        </a>
                        <ul class="submenu" style="padding-inline-start: 0rem">
                            @canany(['campaign-list', 'campaign-create', 'campaign-update', 'campaign-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('campaigns.index') }}">{{ __('Campaigns') }}</a>
                                </li>
                            @endcanany
                            @canany(['promotion-list', 'promotion-create', 'promotion-update', 'promotion-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('promotions.index') }}">{{ __('Promotions & Offer Zones') }}</a>
                                </li>
                            @endcanany
                            @canany(['promotion-item-list', 'promotion-item-update', 'promotion-item-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('promotions.items') }}">{{ __('Promotional Items') }}</a>
                                </li>
                            @endcanany
                            @canany(['ad-promotion-list', 'ad-promotion-update', 'ad-promotion-delete'])
                                <li class="submenu-item">
                                     <a href="{{ route('ad-promotions.index') }}">{{ __('Promoted Ads') }}</a>
                                </li>
                                <li class="submenu-item">
                                     <a href="{{ route('ad-promotions.user-analytics') }}">{{ __('User Promotions Analytics') }}</a>
                                </li>
                            @endcanany
                        </ul>
                    </li>
                @endcanany

                @canany(['seller-verification-field-list', 'seller-verification-field-create',
                    'seller-verification-field-update', 'seller-verification-field-delete',
                    'seller-verification-request-list', 'seller-verification-request-create',
                    'seller-verification-request-update', 'seller-verification-request-delete', 'seller-review-list',
                    'seller-review-update', 'seller-review-delete', 'store-list', 'store-update', 'store-delete',
                    'seller-qr-list', 'seller-qr-manage', 'seller-qr-setting'])
                    <li class="sidebar-item has-sub">
                        <a href="#" class='sidebar-link'>
                            <i class="ph ph-user-focus"></i>
                            <span class="menu-item">{{ __('Seller Management') }}</span>
                        </a>
                        <ul class="submenu" style="padding-inline-start: 0rem">
                            @canany(['store-list', 'store-update', 'store-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('stores.index') }}">{{ __('Stores & Shops') }}</a>
                                </li>
                            @endcanany
                            @canany(['seller-qr-list', 'seller-qr-manage', 'seller-qr-setting'])
                                <li class="submenu-item">
                                    <a href="{{ route('seller-qr.index') }}">{{ __('Seller QR Codes') }}</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="{{ route('seller-qr.settings') }}">{{ __('QR Code Settings') }}</a>
                                </li>
                            @endcanany
                            @canany(['seller-verification-field-list', 'seller-verification-field-create',
                                'seller-verification-field-update', 'seller-verification-field-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('seller-verification.verification-field') }}">{{ __('Verification Fields') }}</a>
                                </li>
                            @endcanany
                            @canany(['seller-verification-request-list', 'seller-verification-request-create',
                                'seller-verification-request-update', 'seller-verification-request-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('seller-verification.index') }}">{{ __('Seller Verification') }}</a>
                                </li>
                            @endcanany
                            @canany(['seller-review-list', 'seller-review-update', 'seller-review-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('seller-review.index') }}">{{ __('Seller Review') }}</a>
                                </li>
                            @endcanany
                            @canany(['seller-review-list', 'seller-review-update', 'seller-review-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('review-report.index') }}">{{ __('Seller Review Report') }}</a>
                                </li>
                            @endcanany
                        </ul>
                    </li>
                @endcanany
                @canany(['blog-list', 'blog-create', 'blog-update', 'blog-delete',
                    'faq-create', 'faq-list', 'faq-update',  'faq-delete'])
                    <li class="sidebar-item has-sub">
                        <a href="#" class='sidebar-link'>
                            <i class="ph ph-article"></i>
                            <span class="menu-item">{{ __('Forms & Blogs') }}</span>
                        </a>
                        <ul class="submenu" style="padding-inline-start: 0rem">

                            @canany(['blog-list', 'blog-create', 'blog-update', 'blog-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('blog.index') }}">{{ __('Blogs') }}</a>
                                </li>
                                <li class="submenu-item">
                                    <a href="{{ route('blog-category.index') }}">{{ __('Blog Category') }}</a>
                                </li>
                            @endcanany
                            @canany(['faq-create', 'faq-list', 'faq-update', 'faq-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('faq.index') }}">{{ __('FAQs') }}</a>
                                </li>
                            @endcanany
                        </ul>
                    </li>
                @endcanany


                @canany(['role-list', 'role-create', 'role-update', 'role-delete', 'staff-list', 'staff-create',
                    'staff-update', 'staff-delete', 'customer-list', 'customer-create', 'customer-update', 'customer-delete',
                    'user-queries-list'])
                    <li class="sidebar-item has-sub">
                        <a href="#" class='sidebar-link'>
                            <i class="ph ph-users-three"></i>
                            <span class="menu-item">{{ __('Customers & Roles') }}</span>
                        </a>
                        <ul class="submenu" style="padding-inline-start: 0rem">
                            @canany(['customer-list', 'customer-create', 'customer-update', 'customer-delete'])
                                <li class="submenu-item">
                                    <a href="{{ url('customer') }}">{{ __('Customers') }}</a>
                                </li>
                            @endcanany
                            @canany(['role-list', 'role-create', 'role-update', 'role-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('roles.index') }}">{{ __('Role') }}</a>
                                </li>
                            @endcanany
                            @canany(['staff-list', 'staff-create', 'staff-update', 'staff-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('staff.index') }}">{{ __('Staff Management') }}</a>
                                </li>
                            @endcanany
                            @canany(['user-queries-list'])
                                <li class="submenu-item">
                                    <a href="{{ route('contact-us.index') }}">{{ __('User Queries') }}</a>
                                </li>
                            @endcanany
                        </ul>
                    </li>
                @endcanany
                @canany(['currency-list', 'currency-create', 'currency-update', 'currency-delete', 'country-list',
                    'country-create', 'country-update', 'country-delete', 'state-list', 'state-create', 'state-update',
                    'state-delete', 'city-list', 'city-create', 'city-update', 'city-delete','area-create', 'area-list', 'area-update', 'area-delete'])
                    <li class="sidebar-item has-sub">
                        <a href="#" class='sidebar-link'>
                            <i class="ph ph-globe-hemisphere-east"></i>
                            <span class="menu-item">{{ __('Location') }}</span>
                        </a>
                        <ul class="submenu" style="padding-inline-start: 0rem">
                            @canany(['currency-list', 'currency-create', 'currency-update', 'currency-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('currency.index') }}">{{ __('Currencies') }}</a>
                                </li>
                            @endcanany
                            @canany(['country-list', 'country-create', 'country-update', 'country-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('countries.index') }}">{{ __('Countries') }}</a>
                                </li>
                            @endcanany
                            @canany(['state-list', 'state-create', 'state-update', 'state-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('states.index') }}">{{ __('States') }}</a>
                                </li>
                            @endcanany
                            @canany(['city-list', 'city-create', 'city-update', 'city-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('cities.index') }}">{{ __('Cities') }}</a>
                                </li>
                            @endcanany
                            @canany(['area-list', 'area-create', 'area-update', 'area-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('area.index') }}">{{ __('Areas') }}</a>
                                </li>
                            @endcanany
                        </ul>
                    </li>
                @endcanany
                @canany(['report-reason-list', 'report-reason-create', 'report-reason-update', 'report-reason-delete', 'user-reports-list'])
                    <li class="sidebar-item has-sub">
                        <a href="#" class='sidebar-link'>
                            <i class="ph ph-flag"></i>
                            <span class="menu-item">{{ __('Reports') }}</span>
                        </a>
                        <ul class="submenu" style="padding-inline-start: 0rem">
                            @canany(['report-reason-list', 'report-reason-create', 'report-reason-update', 'report-reason-delete'])
                                <li class="submenu-item">
                                    <a href="{{ route('report-reasons.index') }}">{{ __('Report Reasons') }}</a>
                                </li>
                            @endcanany
                            @canany(['user-reports-list'])
                                <li class="submenu-item">
                                    <a href="{{ route('report-reasons.user-reports.index') }}">{{ __('User Reports') }}</a>
                                </li>
                            @endcanany
                        </ul>
                    </li>
                @endcanany
                @canany(['notification-list', 'notification-create', 'notification-update', 'notification-delete'])
                    <li class="sidebar-item">
                        <a href="{{ url('notification') }}" class='sidebar-link'>
                            <i class="ph ph-bell"></i>
                            <span class="menu-item">{{ __('Notification') }}</span>
                        </a>
                    </li>
                @endcanany

                @can('settings-update')
                <div class="sidebar-new-title">{{ __('Settings') }}</div>
                @endcan
                @can('settings-update')
                    @if (\Illuminate\Support\Facades\Auth::user()->hasRole('Super Admin'))
                        <li class="sidebar-item">
                            <a href="{{ route('plugin-manager.index') }}" class='sidebar-link'>
                                <i class="ph ph-puzzle-piece"></i>
                                <span class="menu-item">{{ __('Plugin Manager') }}</span>
                            </a>
                        </li>
                    @endif
                @endcan
                @can('settings-update')
                    <li class="sidebar-item">
                        <a href="{{ route('settings.index') }}" class='sidebar-link'>
                            <i class="ph ph-gear"></i>
                            <span class="menu-item">{{ __('Settings') }}</span>
                        </a>
                    </li>
                @endcan
               @can('settings-update')
                    @if (\Illuminate\Support\Facades\Auth::user()->hasRole('Super Admin'))
                        <li class="sidebar-item">
                            <a href="{{ route('system-update.index') }}" class='sidebar-link'>
                                <i class="ph ph-gear-fine"></i>
                                <span class="menu-item">{{ __('System Update') }}</span>
                            </a>
                        </li>
                    @endif
                @endcan
            </ul>
        </div>
    </div>
</div>
