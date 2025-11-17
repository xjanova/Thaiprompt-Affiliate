{{--
    Laravel Echo Configuration Component

    Configuration สำหรับ WebSocket real-time notifications

    @props
    - None

    @example
    <x-echo-config />
--}}

<script>
    /**
     * Laravel Echo Configuration
     *
     * Config จะถูกโหลดจาก environment variables
     */
    window.echoConfig = {
        driver: '{{ config('broadcasting.default') }}',
        key: '{{ config('broadcasting.connections.pusher.key') }}',
        cluster: '{{ config('broadcasting.connections.pusher.options.cluster') }}',
        wsHost: '{{ config('broadcasting.connections.pusher.options.host') }}',
        wsPort: {{ config('broadcasting.connections.pusher.options.port', 6001) }},
        wssPort: {{ config('broadcasting.connections.pusher.options.port', 6001) }},
        forceTLS: {{ config('broadcasting.connections.pusher.options.scheme') === 'https' ? 'true' : 'false' }},
        encrypted: {{ config('broadcasting.connections.pusher.options.encrypted', true) ? 'true' : 'false' }},
        authEndpoint: '/broadcasting/auth',
    };

    @auth
        // User Information สำหรับ WebSocket
        window.currentUser = {
            id: {{ auth()->id() }},
            name: '{{ auth()->user()->name }}',
            email: '{{ auth()->user()->email }}',
        };
    @endauth

    console.log('✅ Echo config loaded:', window.echoConfig);
</script>
