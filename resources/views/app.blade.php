<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $page['props']['auth']['lang'] ?? substr(app()->getLocale(), 0, 2)) }}" >
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Zerp') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <script src="{{ asset('js/jquery.min.js') }}"></script>

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
        <style>
            #app-loader{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:#fff}
            #app-loader>div{display:flex;flex-direction:column;align-items:center;gap:1rem}
            #app-loader .spinner{position:relative;width:3rem;height:3rem}
            #app-loader .spinner>div:first-child{width:3rem;height:3rem;border:4px solid #e5e7eb;border-radius:50%;animation:spin 1s linear infinite;border-top-color:#2563eb}
            #app-loader .spinner>div:last-child{position:absolute;inset:0;width:3rem;height:3rem;border:4px solid transparent;border-radius:50%;animation:ping 1s cubic-bezier(0,0,.2,1) infinite;border-top-color:#60a5fa;opacity:.2}
            @keyframes spin{to{transform:rotate(360deg)}}
            @keyframes ping{75%,100%{transform:scale(2);opacity:0}}
        </style>
        <div id="app-loader">
            <div>
                <div class="spinner"><div></div><div></div></div>
                <div style="text-align:center">
                    <h3 style="font-size:1.125rem;font-weight:600;color:#374151">{{ __('Loading...') }}</h3>
                    <p style="font-size:0.875rem;color:#6b7280">{{ __('Please wait while we prepare your webapp...') }}</p>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded',()=>{
                const loader=document.getElementById('app-loader');
                const checkApp=()=>{
                    if(document.querySelector('#app').children.length>0){
                        if(loader)loader.remove();
                    }else{
                        setTimeout(checkApp,50);
                    }
                };
                checkApp();
            });
        </script>
    </body>
</html>
