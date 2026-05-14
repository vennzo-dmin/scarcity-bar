<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="shopify-api-key" content="{{ \Osiset\ShopifyApp\Util::getShopifyConfig('api_key', $shopDomain ?? Auth::user()->name ) }}"/>
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>
    <script src="https://unpkg.com/@shopify/app-bridge@latest"></script>
    <title>{{ config('shopify-app.app_name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/jquery-minicolors/2.3.6/jquery.minicolors.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-minicolors/2.3.6/jquery.minicolors.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="{{ asset('js/get-post.js') }}"></script>
    @yield('styles')
    @stack('styles')
</head>

<body>
<div class="app-wrapper">
    <div class="app-content">
        <main role="main">
            @yield('content')
            @include('bis.components.videoGuide')
        </main>
    </div>
</div>

<script>
    var AppBridge = window['app-bridge'];
    var actions = AppBridge.actions;
    var utils = window['app-bridge-utils'];
    var NavigationMenu = actions.NavigationMenu;
    var AppLink = actions.AppLink;
    var createApp = AppBridge.default;
    var app = createApp({
        apiKey: "{{ \Osiset\ShopifyApp\Util::getShopifyConfig('api_key', base64_decode(\Request::get('host'))) }}",
        shopOrigin: "{{ base64_decode(\Request::get('host')) }}",
        host: "{{ \Request::get('host') }}",
        forceRedirect: true,
    });
    // Navigation links
    var linksArray = [];

    const widgetLink = AppLink.create(app, {
        label: 'Widget',
        destination: '/widget',
    });
    linksArray.push(widgetLink);

    const templatesLink = AppLink.create(app, {
        label: 'Templates',
        destination: '/templates',
    });
    linksArray.push(templatesLink);

    const translationsLink = AppLink.create(app, {
        label: 'Translations',
        destination: '/translations',
    });
    linksArray.push(translationsLink);

    const subscriptionsLink = AppLink.create(app, {
        label: 'Notify list',
        destination: '/subscriptions',
    });
    linksArray.push(subscriptionsLink);

    const automationLink = AppLink.create(app, {
        label: 'Automation',
        destination: '/automation',
    });
    linksArray.push(automationLink);

      const integrationsLink = AppLink.create(app, {
        label: 'Integrations',
        destination: '/providers',
    });
    linksArray.push(integrationsLink);

    const plansLink = AppLink.create(app, {
        label: 'Plans',
        destination: '/plans',
    });
    linksArray.push(plansLink);

    const userGuideLink = AppLink.create(app, {
        label: 'User Guide',
        destination: '/user-guide',
    });
    linksArray.push(userGuideLink);

    const analyticsLink = AppLink.create(app, {
        label: 'Analytics',
        destination: '/analytics',
    });
    linksArray.push(analyticsLink);

    const deliveryLogsLink = AppLink.create(app, {
        label: 'Delivery Logs',
        destination: '/delivery-logs',
    });
    linksArray.push(deliveryLogsLink);

    const navigationMenu = NavigationMenu.create(app, {
        items: linksArray,
        active: undefined,
    });

    const redirect = actions.Redirect.create(app);

    const navigation = function (url) {
        redirect.dispatch(actions.Redirect.Action.APP, url);
    };

    function showToast(app, message, isError = false, duration = 3000) {
        var Toast = actions.Toast;
        const toast = Toast.create(app, {
            message: message,
            duration: duration,
            isError: isError,
        });
        toast.dispatch(Toast.Action.SHOW);
    }


</script>
@yield('scripts')
@stack('scripts')

@if(\Osiset\ShopifyApp\Util::isMPAApplication())
    @include('shopify-app::partials.token_handler')
@endif

</body>
</html>
