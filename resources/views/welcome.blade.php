<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        {{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getSiteName() : 'FreshGo Laundry' }}
        | We Collect · We Clean · We Deliver</title>
    <link rel="icon"
        href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@700;800;900;1000&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --blue: #0046a9;
            --deep: #00327c;
            --green: #29ab20;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: var(--deep);
            font-family: 'Nunito Sans', Arial, sans-serif;
            background: #0076ce;
        }

        a {
            color: inherit;
        }

        .page {
            position: relative;
            min-height: 100vh;
            padding: 0 24px 32px;
            background: #16a9d2 url("{{ asset('images/freshgo-welcome-background-new.jpg') }}") center center / 100% 100% no-repeat fixed;
        }

        .shell {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            max-width: 800px;
            margin: 0 auto 22px;
        }

        .brand {
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: min(100%, 500px);
            min-width: 0;
        }

        .brand-logo {
            display: block;
            width: 100%;
            height: auto;
            aspect-ratio: 5 / 1;
            object-fit: cover;
            object-position: center;
            mix-blend-mode: multiply;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .qr-code {
            display: block;
            width: 82px;
            height: auto;
            border-radius: 10px;
        }

        .tagline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 83%;
            margin-top: -18px;
            font-size: clamp(.7rem, 1.8vw, 1.3rem);
            font-weight: 1000;
            letter-spacing: -.03em;
            white-space: nowrap;
        }

        .tagline b {
            color: #28a82a;
            padding: 0 .22em;
        }

        .delivery {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 7px 13px;
            border: 2px solid #8eeaff;
            border-radius: 999px;
            background: linear-gradient(#0874d4, #003588);
            color: white;
            box-shadow: 0 6px 12px #00759c66, inset 0 1px 4px #fff8;
            font-size: 1rem;
            line-height: 1.05;
        }

        .delivery svg {
            width: 38px;
            height: 38px;
            flex: none;
            fill: none;
            stroke: currentColor;
            stroke-width: 4.5;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .delivery strong,
        .delivery span {
            display: block;
        }

        .delivery span {
            color: #e6ff37;
            font-size: .85rem;
            margin-top: 4px;
        }

        .content {
            max-width: 740px;
            margin: auto;
        }

        .services {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .card {
            min-height: 145px;
            padding: 10px 12px 9px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            gap: 5px;
            border: 2px solid #c8e5f2;
            border-radius: 25px;
            background: linear-gradient(145deg, #fff 15%, #edfaff 65%, #cdebf4);
            box-shadow: 0 5px 10px #0062a24d, inset 0 2px 4px #fff;
            text-decoration: none;
            transition: transform .2s, box-shadow .2s;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 18px #0062a259, inset 0 2px 4px white;
        }

        .card:focus-visible,
        .site-link:focus-visible,
        .brand:focus-visible {
            outline: 3px solid #37a729;
            outline-offset: 4px;
        }

        .card img {
            width: 94px;
            height: 94px;
            object-fit: contain;
            mix-blend-mode: multiply;
        }

        .card svg {
            width: 80px;
            height: 80px;
            color: #0754b7;
            fill: none;
            stroke: currentColor;
            stroke-width: 4;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .card-label {
            width: 100%;
            min-height: 35px;
            display: grid;
            place-items: center;
            padding: 5px 7px;
            border-radius: 999px;
            color: white;
            background: linear-gradient(#1593e7 0%, #0055b9 48%, #003688 100%);
            box-shadow: 0 4px 5px #007bbd66, inset 0 1px 2px #a5e9ff;
            text-align: center;
            text-transform: uppercase;
            font-size: clamp(.76rem, 1.5vw, 1rem);
            font-weight: 1000;
            line-height: 1.1;
        }

        .message {
            margin: 20px auto 12px;
            padding: 12px 28px;
            border: 2px solid #d0f9ff;
            border-radius: 999px;
            background: white;
            color: #00327c;
            box-shadow: 0 5px 10px #005b9b70;
            text-align: center;
            font-size: clamp(.55rem, 1.8vw, 1.25rem);
            font-weight: 1000;
            white-space: nowrap;
        }

        .message span {
            color: #27a829;
        }

        .site-link {
            display: table;
            margin: 0 auto;
            padding: 11px 26px;
            border: 2px solid #42c8fb;
            border-radius: 999px;
            background: linear-gradient(#168de1, #00398c);
            color: white;
            text-decoration: none;
            font-weight: 1000;
            font-size: clamp(1rem, 2vw, 1.3rem);
            box-shadow: 0 5px 10px #005b9b70;
        }

        .site-link svg {
            width: 23px;
            height: 23px;
            vertical-align: -5px;
            margin-right: 8px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
        }

        .steps {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0;
            max-width: 850px;
            margin: 20px auto 0;
            padding: 13px 18px;
            border: 2px solid #bbff8d;
            border-radius: 999px;
            color: white;
            background: linear-gradient(#43c92b, #078319 60%, #006c1b);
            box-shadow: 0 6px 12px #006a8370, inset 0 2px 4px #c2ff9c;
        }

        .step {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex: 1;
            padding: 0 16px;
            text-transform: uppercase;
            white-space: nowrap;
            font-size: clamp(.8rem, 1.7vw, 1.1rem);
            font-weight: 1000;
        }

        .step+.step {
            border-left: 2px solid #b9f6a6;
        }

        .step svg {
            width: 30px;
            height: 30px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.5;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        @media (min-width: 651px) {
            .steps {
                position: fixed;
                left: 21%;
                top: 88.5vh;
                width: 55%;
                max-width: none;
                height: 7vh;
                margin: 0;
                padding: 0 12px;
                border: 0;
                background: transparent;
                box-shadow: none;
            }
        }

        @media (max-width: 650px) {
            .page {
                padding: 0 15px 24px;
                background-size: 100% auto;
                background-position: center top;
                background-attachment: scroll;
            }

            .header {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 16px;
            }

            .brand {
                width: min(100%, 450px);
            }

            .tagline {
                font-size: clamp(.7rem, 3.8vw, 1.2rem);
            }

            .qr-code {
                width: 70px;
            }

            .delivery {
                padding: 7px 12px;
            }

            .delivery svg {
                width: 34px;
                height: 34px;
            }

            .services {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }

            .card {
                min-height: 135px;
                border-radius: 20px;
            }

            .card-label {
                font-size: .78rem;
            }

            .message {
                padding: 10px 5px;
                font-size: clamp(.55rem, 2.55vw, .95rem);
                letter-spacing: -.04em;
                border-radius: 999px;
            }

            .steps {
                border-radius: 999px;
                flex-wrap: nowrap;
                gap: 0;
                padding: 11px 4px;
            }

            .step {
                min-width: 0;
                gap: 4px;
                padding: 0 4px;
                font-size: clamp(.54rem, 2.35vw, .8rem);
            }

            .step svg {
                width: 18px;
                height: 18px;
                flex: none;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .card {
                transition: none;
            }
        }
        .header { max-width: 1200px; display: grid; grid-template-columns: 1fr minmax(220px, 400px) 1fr; gap: 12px; }
        .brand { width: 100%; }
        .brand-logo { width: 100%; height: auto; aspect-ratio: 2.1 / 1; object-fit: cover; object-position: center 46%; }
        .top-nav { display: flex; align-items: center; justify-content: center; gap: 8px; }
        .nav-pill { display: inline-flex; justify-content: center; align-items: center; gap: 8px; min-height: 48px; padding: 8px 16px; border: 2px solid #8eeaff; border-radius: 999px; background: linear-gradient(#1289ea, #0042a6 70%, #003078); color: white; font: inherit; font-size: .95rem; font-weight: 900; text-decoration: none; white-space: nowrap; box-shadow: 0 5px 10px #005b9b70; }
        .nav-pill:focus-visible { outline: 3px solid white; outline-offset: 3px; }
        .nav-pill svg { width: 23px; height: 23px; fill: none; stroke: currentColor; stroke-width: 3; stroke-linecap: round; stroke-linejoin: round; }
        .nav-pill[disabled] { opacity: .8; cursor: not-allowed; }
        .header-actions { justify-content: center; margin: -8px auto 16px; }
        @media (min-width: 801px) { .header-actions { position: fixed; right: 2%; bottom: 22%; margin: 0; z-index: 2; } }
        @media (max-width: 800px) { .header-actions { margin: 16px auto 0; } .header { display: flex; flex-direction: row; flex-wrap: wrap; justify-content: center; } .header .brand { order: -1; flex-basis: 100%; max-width: 350px; } .top-nav { gap: 6px; } .nav-pill { padding: 7px 10px; font-size: .8rem; min-height: 42px; } }
        @media (min-width: 801px) {
            .page { min-height: 100vh; padding-bottom: 8vh; }
            .shell { max-width: 1000px; }
            .header { grid-template-columns: 1fr minmax(200px, 30vh) 1fr; margin-bottom: 1vh; }
            .content { max-width: min(680px, 62vw); }
            .services { gap: 1vh 10px; }
            .card { min-height: 0; height: clamp(95px, 15vh, 135px); padding: 5px 9px 7px; border-radius: 20px; }
            .card img, .card svg { width: clamp(50px, 9vh, 80px); height: clamp(50px, 9vh, 80px); }
            .card-label { min-height: 28px; padding: 3px 6px; }
            .message { margin: 1.3vh auto .8vh; padding: 7px 18px; }
            .site-link { padding: 6px 20px; }
            .header-actions { right: 1%; }
        }
        .menu-dropdown { position: relative; }
        .menu-dropdown summary { cursor: pointer; list-style: none; }
        .menu-dropdown summary::-webkit-details-marker { display: none; }
        .menu-panel { position: absolute; top: calc(100% + 6px); left: 0; z-index: 10; min-width: 180px; padding: 6px; border: 1px solid #b8e8f7; border-radius: 14px; background: white; box-shadow: 0 8px 22px #003b7770; }
        .menu-panel a { display: block; padding: 9px 12px; border-radius: 8px; color: #003b8c; font-size: .9rem; font-weight: 800; text-decoration: none; white-space: nowrap; }
        .menu-panel a:hover, .menu-panel a:focus-visible { background: #e4f6ff; }
        @media (max-width: 650px) {
            .header { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
            .header .brand { grid-column: 1 / -1; grid-row: 1; justify-self: center; }
            .top-nav { gap: 3px; }
            .nav-pill { min-height: 44px; padding: 6px 5px; gap: 3px; font-size: clamp(.68rem, 2.9vw, .84rem); }
            .nav-pill svg { width: 18px; height: 18px; flex: none; }
        }

    </style>
</head>

<body>
    <svg xmlns="http://www.w3.org/2000/svg" style="position:absolute;width:0;height:0;overflow:hidden"
        aria-hidden="true">
        <symbol id="washer" viewBox="0 0 80 80">
            <rect x="11" y="5" width="58" height="70" rx="5" />
            <path d="M11 20h58" />
            <circle cx="40" cy="46" r="20" />
            <path d="M28 49c3 5 6 7 12 7 7 0 13-6 13-13M20 12h8m8 0h1m8 0h1" />
        </symbol>
        <symbol id="shirt" viewBox="0 0 80 80">
            <path d="M27 8 11 14 3 29l12 8 7-9v45h36V28l7 9 12-8-8-15-16-6c-2 7-7 10-13 10S29 15 27 8Z" />
            <path d="m67 51 2 4 4 2-4 2-2 4-2-4-4-2 4-2zM12 50v8m-4-4h8" />
        </symbol>
        <symbol id="dry" viewBox="0 0 80 80">
            <circle cx="40" cy="20" r="13" />
            <path d="M17 70V58c0-12 10-22 23-22s23 10 23 22v12M28 40v13h24V40M30 53l-4 17m24-17 4 17" />
        </symbol>
        <symbol id="iron" viewBox="0 0 80 80">
            <path d="M9 57c6-19 17-31 36-31h19l7 24-5 7H9ZM8 65h65M31 42c3-6 8-9 15-9h9l2 9H31Z" />
        </symbol>
        <symbol id="socks" viewBox="0 0 80 80">
            <path
                d="M15 8h23v26c0 5-2 8-6 12L19 59c-5 5-12 1-12-5 0-3 1-5 4-8l4-4V8Zm32 6h23v25c0 5-2 8-6 12L51 64c-5 5-12 1-12-5 0-3 1-5 4-8l4-4V14ZM15 20h23m9 6h23" />
        </symbol>
        <symbol id="duvet" viewBox="0 0 80 80">
            <path
                d="M13 24c0-7 5-11 13-11h31c8 0 13 4 13 11s-5 11-13 11H23c-6 0-10 4-10 9s4 9 10 9h41M13 44v10c0 8 6 13 14 13h33c7 0 11-4 11-10V46c0-7-5-11-12-11H25M24 22h33" />
        </symbol>
        <symbol id="repair" viewBox="0 0 80 80">
            <circle cx="18" cy="17" r="10" />
            <circle cx="18" cy="63" r="10" />
            <path d="m26 23 43 40M26 57 69 17M42 40h1" />
        </symbol>
        <symbol id="basket" viewBox="0 0 80 80">
            <path d="M9 34h62l-6 35H15L9 34Zm12 0c0-14 8-23 19-23s19 9 19 23M6 34h68M25 43l2 17m13-17v17m15-17-2 17" />
        </symbol>
        <symbol id="friends" viewBox="0 0 80 80">
            <circle cx="27" cy="22" r="12" />
            <circle cx="55" cy="22" r="12" />
            <path d="M5 69V58c0-12 9-20 22-20s22 8 22 20v11H5Zm44 0h26V58c0-12-8-20-20-20-4 0-7 1-10 3" />
        </symbol>
        <symbol id="truck" viewBox="0 0 80 80">
            <path d="M8 22h39v32H8m39-23h13l12 12v11H47M8 32H1m7 9H1m56-10v12h15" />
            <circle cx="22" cy="57" r="7" />
            <circle cx="61" cy="57" r="7" />
        </symbol>
        <symbol id="leaf" viewBox="0 0 80 80">
            <path d="M11 68C8 35 27 13 69 10c-1 35-20 55-52 54M11 68c11-18 23-27 44-38" />
        </symbol>
        <symbol id="globe" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" />
            <path d="M2 12h20M12 2c3 3 4 6 4 10s-1 7-4 10c-3-3-4-6-4-10s1-7 4-10ZM4 7h16M4 17h16" />
        </symbol>
    </svg>
    @php
        $categoryIcons = [
            'wash-fold' => '01_washing_machine.png',
            'wash-iron' => '12_clean_shirt.png',
            'dry-cleaning' => '06_shirt_hanger.png',
            'ironing-only' => '05_iron.png',
            'back-wash-iron' => '08_wash_tub.png',
            'bulky-item-duvet' => '07_folded_laundry.png',
            'repairs' => '13_repairs.svg',
            'others' => '03_laundry_basket.png',
        ];
    @endphp
    <main class="page">
        <div class="shell">
            <header class="header">
                <nav class="top-nav" aria-label="Main navigation">
                    <details class="menu-dropdown"><summary class="nav-pill"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>Menu</summary><div class="menu-panel"><a href="#services">Our services</a><a href="#how-it-works">How to order</a><a href="https://freshgolaundry.co.uk">Visit our website</a></div></details>
                    <a class="nav-pill" href="#how-it-works"><svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9 9a3 3 0 1 1 4 3c-1 .5-1 1-1 2m0 3h.01"/></svg>How to Order</a>
                </nav>
                <a class="brand" href="{{ url('/') }}" aria-label="Freshgo Laundry home">
                    <img class="brand-logo" src="{{ asset('images/freshgo-new-logo.jpeg') }}" alt="Freshgo Laundry — We Collect, We Clean, We Deliver">
                </a>
                <nav class="top-nav" aria-label="Services navigation">
                    <a class="nav-pill" href="{{ route('services.index') }}"><span aria-hidden="true">£</span> Price</a>
                    <a class="nav-pill" href="#services"><svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M19 5l-1.5 1.5m-11 11L5 19"/></svg>Service</a>
                </nav>
            </header>
<div class="content">
                <section id="services" class="services" aria-label="Our laundry services">
                    @foreach ($categories as $category)
                        <a class="card" href="{{ route('services.index', ['category' => $category->slug]) }}">
                            <img src="{{ $category->image_url ?: asset('images/laundry-icons/' . ($categoryIcons[$category->slug] ?? '03_laundry_basket.png')) }}" alt="">
                            <span class="card-label">{{ $category->name }}</span>
                        </a>
                    @endforeach
                    <a class="card" href="#">
                        <img src="{{ asset('images/laundry-icons/14_friend.svg') }}" alt="">
                        <span class="card-label">A Friend</span>
                    </a>
                </section>
                <p class="message">Your <span>Fresh</span> Start – Laundry &amp; Dry Cleaning at Your Doorstep.</p>
                <a class="site-link" href="https://freshgolaundry.co.uk"><svg aria-hidden="true">
                        <use href="#globe" />
                    </svg>freshgolaundry.co.uk</a>
            </div>
            <div class="header-actions">
                <div class="delivery"><svg aria-hidden="true"><use href="#truck" /></svg><div><strong>Freshgo</strong><span>Express Delivery</span></div></div>
                <img class="qr-code" src="{{ asset('images/freshgo-qr.jpg') }}" alt="Scan the Freshgo Laundry QR code">
            </div>
            <div id="how-it-works" class="steps" aria-label="We collect, we clean, we deliver">
                <div class="step"><svg aria-hidden="true">
                        <use href="#leaf" />
                    </svg>We Collect</div>
                <div class="step"><svg aria-hidden="true">
                        <use href="#basket" />
                    </svg>We Clean</div>
                <div class="step"><svg aria-hidden="true">
                        <use href="#truck" />
                    </svg>We Deliver</div>
            </div>
        </div>
    </main>
</body>

</html>




























