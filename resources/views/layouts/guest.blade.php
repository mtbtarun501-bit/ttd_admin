<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;800&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased" style="background-image: url('{{ asset('assets/theme/images/premium-bg-clean-wide.jpg') }}'); background-size: cover; background-position: center bottom; background-attachment: fixed; background-repeat: no-repeat; margin: 0; padding: 0; image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges;">
        
        <!-- Top Left Golden Logo -->
        <div class="auth-branding-desktop" style="position: absolute; top: 40px; left: 50px; display: flex; flex-direction: column; align-items: flex-start; z-index: 50; pointer-events: none;">
            <div style="font-size: 38px; margin-bottom: 2px; background: linear-gradient(to right, #bf953f, #fcf6ba, #b38728, #fbf5b7, #aa771c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.6));">
                <i class="fa-solid fa-om" style="font-family: 'FontAwesome'; font-style: normal;"></i>
            </div>
            <h1 style="margin: 0; font-size: 28px; font-weight: 800; letter-spacing: 2px; font-family: 'Cinzel', serif; background: linear-gradient(135deg, #bf953f, #fcf6ba, #b38728, #fbf5b7, #aa771c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.5));">
                GARUDA DIVINE BOOKINGS
            </h1>
            <p style="margin: 6px 0 0 2px; color: #f3f4f6; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 3.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.8); font-family: 'figtree', sans-serif;">
                Tirumala Alipiri Entrance
            </p>
        </div>

        <!-- Bottom Left Contact Info -->
        <div class="auth-contact-desktop" style="position: absolute; bottom: 40px; left: 50px; display: flex; flex-direction: row; align-items: center; z-index: 50; background: rgba(15, 10, 5, 0.65); padding: 15px 30px; border-radius: 12px; border-left: 4px solid #c89b3c; backdrop-filter: blur(5px); box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
            <div style="display: flex; align-items: center; margin-right: 40px;">
                <i class="fa-solid fa-headset" style="color: #c89b3c; margin-right: 15px; font-size: 18px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.8));"></i>
                <div style="display: flex; flex-direction: column;">
                    <span style="color: #e5e7eb; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-family: 'figtree', sans-serif; margin-bottom: 2px;">Ganesh Gowtham</span>
                    <span style="font-size: 18px; font-weight: 800; font-family: 'Cinzel', serif; background: linear-gradient(to right, #bf953f, #fcf6ba, #b38728, #fbf5b7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.9));">+91 91773 93752</span>
                </div>
            </div>
            <div style="display: flex; align-items: center;">
                <i class="fa-brands fa-whatsapp" style="color: #c89b3c; margin-right: 15px; font-size: 18px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.8));"></i>
                <div style="display: flex; flex-direction: column;">
                    <span style="color: #e5e7eb; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-family: 'figtree', sans-serif; margin-bottom: 2px;">Sidhu Vinay</span>
                    <span style="font-size: 18px; font-weight: 800; font-family: 'Cinzel', serif; background: linear-gradient(to right, #bf953f, #fcf6ba, #b38728, #fbf5b7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.9));">+91 98499 93991</span>
                </div>
            </div>
        </div>

        <div class="min-h-screen w-full flex items-center pt-6 sm:pt-0 auth-container" style="background-color: rgba(0, 0, 0, 0.4); justify-content: flex-end; padding-right: 12%;">
            <div style="display: flex; flex-direction: column; align-items: center; width: 100%; max-width: 420px;">
                <div class="w-full mt-6 px-8 py-8 bg-white shadow-xl rounded-xl garuda-login-box">
                    <!-- Visible Heading matching Dashboard Navbar -->
                    <div class="text-center mb-8">
                        <div style="font-size: 32px; color: #c89b3c; margin-bottom: 5px;">
                            <i class="fa-solid fa-om" style="font-family: 'FontAwesome'; font-style: normal;"></i>
                        </div>
                        <h2 style="color: #2e1a11; font-size: 22px; font-weight: 800; margin: 0; letter-spacing: 1px;">GARUDA <span style="color: #c89b3c;">DIVINE BOOKINGS</span></h2>
                        <p style="color: #666; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 2px; margin-top: 5px;">Tirumala Alipiri Entrance</p>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>

        <style>
            /* Garuda Dashboard Theme Matching for Login Box */
            .garuda-login-box {
                border-top: 5px solid #c89b3c; /* Garuda Gold */
                background-color: #ffffff !important; /* Pure White like Dashboard Cards */
                border-radius: 16px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3) !important;
                padding: 40px 40px 30px 40px !important;
                box-sizing: border-box;
            }
            .garuda-login-box label {
                color: #2e1a11 !important; /* Garuda Dark Brown */
                font-weight: 700;
                font-size: 14px;
                margin-bottom: 8px;
                display: block;
            }
            .garuda-login-box input[type="email"],
            .garuda-login-box input[type="password"],
            .garuda-login-box input[type="text"] {
                box-sizing: border-box !important;
                border: 1px solid #d1d5db !important;
                border-radius: 8px !important;
                padding: 12px 16px !important;
                background-color: #f9fafb !important;
                width: 100% !important;
                color: #111827 !important;
                font-size: 15px !important;
                transition: all 0.2s;
                margin-bottom: 5px;
            }
            .garuda-login-box input:focus {
                border-color: #c89b3c !important;
                background-color: #ffffff !important;
                box-shadow: 0 0 0 3px rgba(200, 155, 60, 0.2) !important;
                outline: none !important;
            }
            .garuda-login-box button {
                box-sizing: border-box !important;
                background-color: #c89b3c !important;
                color: #ffffff !important;
                font-weight: 700 !important;
                letter-spacing: 1px;
                text-transform: uppercase;
                border-radius: 6px !important;
                width: 100% !important;
                justify-content: center;
                padding: 12px !important;
                border: none !important;
                box-shadow: 0 4px 6px rgba(200, 155, 60, 0.2) !important;
                transition: all 0.3s ease;
                margin-top: 10px;
            }
            .garuda-login-box button:hover {
                background-color: #b88a30 !important;
                transform: translateY(-1px);
                box-shadow: 0 6px 12px rgba(200, 155, 60, 0.3) !important;
            }
            .garuda-login-box a {
                color: #c89b3c !important;
                text-decoration: none;
                transition: color 0.2s;
            }
            .garuda-login-box a:hover {
                color: #2e1a11 !important;
            }
            
            /* Fix browser autofill */
            .garuda-login-box input:-webkit-autofill {
                -webkit-box-shadow: 0 0 0 30px #ffffff inset !important;
                -webkit-text-fill-color: #333 !important;
            }
            
            /* Responsive Styles for Mobile Auth */
            @media (max-width: 991.98px) {
                .auth-branding-desktop, .auth-contact-desktop {
                    display: none !important;
                }
                .auth-container {
                    justify-content: center !important;
                    padding-right: 0 !important;
                    padding: 20px !important;
                }
                .garuda-login-box {
                    padding: 30px 20px 25px 20px !important;
                }
            }
        </style>
    </body>
</html>
