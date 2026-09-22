<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}</title>
    <meta name="description" content="{{ config('site.description') }}">
    <meta name="author" content="{{ config('app.name') }}">
    
    <meta property="og:title" content="{{ config('app.name') }}">
    <meta property="og:description" content="{{ config('site.description') }}">
    <meta property="og:type" content="website">
    @if(config('site.favicon'))<link rel="icon" href="{{ asset(config('site.favicon')) }}">@endif

    @vite(['resources/css/icons.css'])
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Snowstorm/20131208/snowstorm-min.js" integrity="sha512-rMkLONrw50boYG/6Ku0E8VstfWMRn5D0dX3QZS26Mg0rspYq4EHxYOULuPbv9Be2HBbrrmN8dpPgYUeJ4bINCA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        background: 'hsl(0 0% 8%)',
                        foreground: 'hsl(0 0% 98%)',
                        card: 'hsl(0 0% 10%)',
                        'card-foreground': 'hsl(0 0% 95%)',
                        primary: 'hsl(0 0% 98%)',
                        'primary-foreground': 'hsl(0 0% 8%)',
                        secondary: 'hsl(0 0% 15%)',
                        'secondary-foreground': 'hsl(0 0% 90%)',
                        muted: 'hsl(0 0% 12%)',
                        'muted-foreground': 'hsl(0 0% 65%)',
                        accent: 'hsl(200 100% 50%)',
                        'accent-foreground': 'hsl(0 0% 8%)',
                        destructive: 'hsl(0 75% 60%)',
                        border: 'hsl(0 0% 18%)',
                        'server-online': 'hsl(200 100% 50%)',
                        'server-offline': 'hsl(0 75% 60%)'
                    }
                }
            }
        }
    </script>
    <style>
        body { 
            background-color: hsl(0 0% 8%); 
            color: hsl(0 0% 98%);
        }
        .card {
            background-color: hsl(0 0% 10%);
            border: 1px solid hsl(0 0% 18%);
        }
        .btn-primary {
            background-color: hsl(0 0% 98%);
            color: hsl(0 0% 8%);
        }
        .btn-primary:hover {
            background-color: hsl(0 0% 90%);
        }
        .btn-outline {
            border: 1px solid hsl(0 0% 18%);
            background-color: transparent;
            color: hsl(0 0% 98%);
        }
        .btn-outline:hover {
            background-color: hsl(0 0% 15%);
        }
        .btn-login {
            background-color: hsl(200 100% 50%);
            color: hsl(0 0% 8%);
        }
        .btn-login:hover {
            background-color: hsl(200 100% 45%);
        }
        .badge-secondary {
            background-color: hsl(0 0% 15%);
            color: hsl(0 0% 90%);
        }
        .badge-outline {
            border: 1px solid hsl(0 0% 18%);
            color: hsl(0 0% 90%);
            background-color: transparent;
        }
        .status-online {
            color: hsl(200 100% 50%);
        }
        
        /* Form styles */
        .form-input {
            background-color: hsl(0 0% 12%);
            border: 1px solid hsl(0 0% 18%);
            color: hsl(0 0% 98%);
            padding: 0.5rem;
            border-radius: 0.375rem;
        }
        .form-input:focus {
            outline: none;
            border-color: hsl(200 100% 50%);
            box-shadow: 0 0 0 2px hsl(200 100% 50% / 0.2);
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
        }
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background-color: hsl(0 0% 10%);
            border: 1px solid hsl(0 0% 18%);
            border-radius: 0.5rem;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
        }
    </style>
</head>
<body class="min-h-screen">
    <div class="min-h-screen p-6 md:p-8">
        <!-- Top Navigation -->
        <div class="max-w-4xl mx-auto mb-8">
            <div class="flex justify-end gap-3">
                <a href="{{ route('login') }}" class="btn-login px-4 py-2 rounded-md font-medium transition-colors hover:scale-105 transform text-decoration-none">
                    <i class="fa-solid fa-right-to-bracket"></i> Login
                </a>
                @if(config('wow.registration_enabled'))
                <a href="{{ route('register') }}" class="btn-outline px-4 py-2 rounded-md font-medium transition-colors hover:scale-105 transform text-decoration-none">
                    <i class="fa-solid fa-user-plus"></i> Register
                </a>
                @endif
            </div>
        </div>
        
        <div class="max-w-4xl mx-auto space-y-8">
            <!-- Header -->
            <header class="text-center space-y-4">
                <div class="w-24 h-24 mx-auto flex items-center justify-center mb-6">
                    <x-application-logo />
                </div>
                <h1 class="text-4xl font-bold tracking-tight">{{ config('app.name') }}</h1>
                <p style="color: hsl(0 0% 65%);" class="text-lg">{{ config('site.description') }}</p>
            </header>

            <!-- Server Status -->
            <div class="card p-6 rounded-lg">
                <div class="flex items-center gap-2 mb-4">
                    <i class="fa-solid fa-bolt-lightning"></i>
                    <h2 class="text-xl font-semibold">Server Status</h2>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center space-y-2">
                        <div class="text-2xl font-bold status-online">Online</div>
                        <div class="text-sm" style="color: hsl(0 0% 65%);">Status</div>
                    </div>
                    <div class="text-center space-y-2">
                        <div class="text-2xl font-bold">{{ $onlineCount }}</div>
                        <div class="text-sm" style="color: hsl(0 0% 65%);">Online</div>
                    </div>
                    <div class="text-center space-y-2">
                        <div class="text-2xl font-bold">3.3.5a</div>
                        <div class="text-sm" style="color: hsl(0 0% 65%);">Expansion</div>
                    </div>
                    <div class="text-center space-y-2">
                        <div class="text-2xl font-bold">{{ config('wow.rates.xp') }}</div>
                        <div class="text-sm" style="color: hsl(0 0% 65%);">Rates</div>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-8">
                <!-- Connection Guide -->
                <div class="card p-6 rounded-lg">
                    <h2 class="text-xl font-semibold mb-4">How to Connect</h2>
                    <div class="space-y-4">
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-sm font-bold">1</div>
                            <div class="flex-1">
                                <h4 class="font-medium">Download Client</h4>
                                <p class="text-sm mb-2" style="color: hsl(0 0% 65%);">Get the game client to start playing</p>
                                @if($downloadUrl)
                                <a href="{{ config('wow.client_download_url') }}" class="btn-outline px-4 py-2 text-sm rounded-md transition-colors flex items-center gap-2 inline-flex">
                                    <i class="fa-solid fa-download"></i>
                                    Download
                                </a>
                                @endif
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-sm font-bold">2</div>
                            <div class="flex-1">
                                <h4 class="font-medium">Update Realmlist</h4>
                                <p class="text-sm mb-2" style="color: hsl(0 0% 65%);">Change your realmlist.wtf file to:</p>
                                <code class="block text-xs p-2 rounded font-mono" style="background-color: hsl(0 0% 12%);">
                                    {{ $realmlist }}
                                </code>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="w-6 h-6 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-sm font-bold">3</div>
                            <div class="flex-1">
                                <h4 class="font-medium">Create Account</h4>
                                <p class="text-sm mb-2" style="color: hsl(0 0% 65%);">Register an account to start playing</p>
                                @if(config('wow.registration_enabled'))
                                <a href="{{ route('register') }}" class="btn-primary px-4 py-2 text-sm rounded-md transition-colors flex items-center gap-2 text-decoration-none inline-flex">
                                    <i class="fa-regular fa-user"></i>
                                    Register
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- News -->
                <div class="card p-6 rounded-lg">
                    <h2 class="text-xl font-semibold mb-4">Latest News</h2>
                    <div class="space-y-4">
                        <article class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="badge-secondary px-2 py-1 text-xs rounded">Update</span>
                                <span class="text-xs" style="color: hsl(0 0% 65%);">2 days ago</span>
                            </div>
                            <h4 class="font-medium">Server Maintenance Complete</h4>
                            <p class="text-sm" style="color: hsl(0 0% 65%);">
                                Performance improvements and bug fixes have been deployed.
                            </p>
                        </article>
                        
                        <article class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="badge-outline px-2 py-1 text-xs rounded">Event</span>
                                <span class="text-xs" style="color: hsl(0 0% 65%);">1 week ago</span>
                            </div>
                            <h4 class="font-medium">Double XP Weekend</h4>
                            <p class="text-sm" style="color: hsl(0 0% 65%);">
                                Enjoy doubled experience rates this weekend only!
                            </p>
                        </article>
                        
                        <article class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span class="badge-outline px-2 py-1 text-xs rounded">Announcement</span>
                                <span class="text-xs" style="color: hsl(0 0% 65%);">2 weeks ago</span>
                            </div>
                            <h4 class="font-medium">Server Launch</h4>
                            <p class="text-sm" style="color: hsl(0 0% 65%);">
                                Server is now live! Join us and start your adventure!
                            </p>
                        </article>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <footer class="text-center text-sm border-t pt-6" style="color: hsl(0 0% 65%); border-color: hsl(0 0% 18%);">
                <p>© {{ date('Y') }} {{ config('app.name') }}</p>
            </footer>
        </div>
    </div>
    <script>
    snowStorm.followMouse = false;
    </script>
</body>
</html>
