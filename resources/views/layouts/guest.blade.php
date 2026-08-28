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
        
        <style>
            body {
                margin: 0;
                padding: 0;
                overflow-x: hidden;
                background-color: #0f0a05;
                font-family: 'figtree', sans-serif;
            }
            
            /* Scroll Container */
            .cinematic-scroll-container {
                height: 350vh; /* Determines how much the user has to scroll */
                position: relative;
            }

            /* Pinned Scene */
            .pinned-scene {
                position: sticky;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                overflow: hidden;
                display: flex;
                align-items: center;
                justify-content: flex-end;
            }

            /* Cinematic Background Layers */
            .cinematic-bg-layer {
                position: absolute;
                top: -5%;
                left: -5%;
                width: 110%;
                height: 110%;
                background-image: url('{{ asset('assets/theme/images/premium-bg-clean-wide.jpg') }}');
                background-size: cover;
                background-position: center bottom;
                background-repeat: no-repeat;
                z-index: 0;
                transform-origin: 60% 45%; /* Zoom towards the second background gopuram */
                will-change: transform;
                image-rendering: -webkit-optimize-contrast;
                image-rendering: crisp-edges;
            }
            
            /* Overlay to darken background when form appears */
            .cinematic-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(135deg, rgba(15,10,5,0.1) 0%, rgba(15,10,5,0.7) 100%);
                z-index: 1;
                opacity: 0;
                will-change: opacity;
            }

            /* UI Layers */
            .auth-branding-desktop {
                position: absolute;
                top: 40px;
                left: 50px;
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                z-index: 50;
                pointer-events: none;
            }

            .auth-contact-desktop {
                position: absolute;
                bottom: 40px;
                left: 50px;
                display: flex;
                flex-direction: row;
                align-items: center;
                z-index: 50;
                background: rgba(15, 10, 5, 0.65);
                padding: 15px 30px;
                border-radius: 12px;
                border-left: 4px solid #c89b3c;
                backdrop-filter: blur(5px);
                box-shadow: 0 10px 25px rgba(0,0,0,0.5);
                transition: opacity 0.5s ease;
            }
            
            .auth-container {
                position: relative;
                z-index: 100;
                width: 100%;
                height: 100%;
                display: flex;
                align-items: center;
                justify-content: flex-end;
                padding-right: 12%;
                opacity: 0;
                visibility: hidden;
                transform: translateY(80px) scale(0.95);
                will-change: opacity, transform, visibility;
            }

            /* Garuda Dashboard Theme Matching for Login Box */
            .garuda-login-box {
                border-top: 5px solid #c89b3c;
                background-color: #ffffff !important;
                border-radius: 16px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.3) !important;
                padding: 40px 40px 30px 40px !important;
                box-sizing: border-box;
                width: 100%;
                max-width: 420px;
            }
            .garuda-login-box label {
                color: #2e1a11 !important;
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
                .cinematic-scroll-container {
                    height: 250vh; /* Shorter scroll on mobile */
                }
                .cinematic-bg-layer {
                    transform-origin: 50% 50%; /* Center focus on mobile to prevent extreme cropping */
                }
            }

            /* Fallback for users who prefer reduced motion */
            @media (prefers-reduced-motion: reduce) {
                .cinematic-scroll-container {
                    height: 100vh;
                }
                .pinned-scene {
                    position: relative;
                }
                .auth-container {
                    opacity: 1;
                    visibility: visible;
                    transform: none;
                }
                .cinematic-overlay {
                    opacity: 1;
                }
            }
        </style>
    </head>
    <body>
        <div class="cinematic-scroll-container">
            <div class="pinned-scene">
                <!-- Background Layer -->
                <div class="cinematic-bg-layer" id="cinematic-bg"></div>
                <div class="cinematic-overlay" id="cinematic-overlay"></div>
                
                <!-- Top Left Golden Logo -->
                <div class="auth-branding-desktop" id="auth-branding">
                    <div style="font-size: 38px; margin-bottom: 2px; background: linear-gradient(to right, #bf953f, #fcf6ba, #b38728, #fbf5b7, #aa771c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.6));">
                        <i class="fa-solid fa-om" style="font-family: 'FontAwesome'; font-style: normal;"></i>
                    </div>
                    <h1 style="margin: 0; font-size: 28px; font-weight: 800; letter-spacing: 2px; font-family: 'Cinzel', serif; background: linear-gradient(135deg, #bf953f, #fcf6ba, #b38728, #fbf5b7, #aa771c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.5));">
                        GARUDA DIVINE BOOKINGS
                    </h1>
                    <p style="margin: 6px 0 0 2px; color: #f3f4f6; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 3.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.8);">
                        Tirumala Alipiri Entrance
                    </p>
                </div>

                <!-- Bottom Left Contact Info -->
                <div class="auth-contact-desktop" id="auth-contact">
                    <div style="display: flex; align-items: center; margin-right: 40px;">
                        <i class="fa-solid fa-headset" style="color: #c89b3c; margin-right: 15px; font-size: 18px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.8));"></i>
                        <div style="display: flex; flex-direction: column;">
                            <span style="color: #e5e7eb; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 2px;">Ganesh Gowtham</span>
                            <span style="font-size: 18px; font-weight: 800; font-family: 'Cinzel', serif; background: linear-gradient(to right, #bf953f, #fcf6ba, #b38728, #fbf5b7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.9));">+91 91773 93752</span>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center;">
                        <i class="fa-brands fa-whatsapp" style="color: #c89b3c; margin-right: 15px; font-size: 18px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.8));"></i>
                        <div style="display: flex; flex-direction: column;">
                            <span style="color: #e5e7eb; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 2px;">Sidhu Vinay</span>
                            <span style="font-size: 18px; font-weight: 800; font-family: 'Cinzel', serif; background: linear-gradient(to right, #bf953f, #fcf6ba, #b38728, #fbf5b7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.9));">+91 98499 93991</span>
                        </div>
                    </div>
                </div>

                <!-- Login Form Container -->
                <div class="auth-container" id="auth-container">
                    <div style="display: flex; flex-direction: column; align-items: center; width: 100%; max-width: 420px;">
                        <div class="garuda-login-box">
                            <!-- Visible Heading matching Dashboard Navbar -->
                            <div class="text-center mb-8" style="text-align: center; margin-bottom: 2rem;">
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
            </div>
        </div>

        <script type="module">
            document.addEventListener('DOMContentLoaded', () => {
                // Check for reduced motion preference
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    return;
                }

                // Give Vite a moment to load GSAP if it hasn't already (though type="module" generally handles this well)
                const initGSAP = () => {
                    if (typeof window.gsap !== 'undefined' && typeof window.ScrollTrigger !== 'undefined') {
                        const bgLayer = document.getElementById('cinematic-bg');
                        const overlay = document.getElementById('cinematic-overlay');
                        const authContainer = document.getElementById('auth-container');
                        
                        // GSAP Scroll Timeline
                        const tl = window.gsap.timeline({
                            scrollTrigger: {
                                trigger: '.cinematic-scroll-container',
                                start: 'top top',
                                end: 'bottom bottom',
                                scrub: 1.2, // Smooth interpolation
                            }
                        });

                        // 1. Zoom into the background gopuram
                        tl.to(bgLayer, {
                            scale: 1.5,
                            ease: "power2.inOut",
                            duration: 1
                        }, 0);

                        // 2. Fade in the overlay slightly after scroll begins
                        tl.to(overlay, {
                            opacity: 1,
                            ease: "power1.inOut",
                            duration: 0.6
                        }, 0.4);

                        // 3. Reveal the login container towards the end of the scroll
                        tl.to(authContainer, {
                            autoAlpha: 1, // handles visibility and opacity
                            y: 0,
                            scale: 1,
                            ease: "power3.out",
                            duration: 0.5
                        }, 0.5);

                        // Subtle Mouse Parallax (2.5D Effect)
                        if (!window.matchMedia('(max-width: 991.98px)').matches) {
                            const xTo = window.gsap.quickTo(bgLayer, "x", {duration: 0.8, ease: "power3"});
                            const yTo = window.gsap.quickTo(bgLayer, "y", {duration: 0.8, ease: "power3"});
                            
                            window.addEventListener("mousemove", (e) => {
                                // Calculate subtle offset (-15px to 15px max)
                                const xOffset = (e.clientX / window.innerWidth - 0.5) * 30; 
                                const yOffset = (e.clientY / window.innerHeight - 0.5) * 30;
                                xTo(-xOffset);
                                yTo(-yOffset);
                            });
                        }
                    } else {
                        // Retry if gsap isn't attached to window yet
                        setTimeout(initGSAP, 50);
                    }
                };

                initGSAP();
            });
        </script>
    </body>
</html>
