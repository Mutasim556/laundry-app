<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getSiteName() : 'FreshGo Laundry' }} | Professional Laundry & Dry Cleaning</title>
<link rel="icon" href="{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFaviconUrl() : asset('favicon.ico') }}">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Manrope:wght@700;800&display=swap" rel="stylesheet">
<style>
:root{--primary:#0AA7E0;--dark:#0F2233;--light:#F5FBFF;--accent:#00D1B2}
*{margin:0;padding:0;box-sizing:border-box} body{font-family:'Inter',sans-serif;color:var(--dark);background:#fff}
.container{max-width:1140px;margin:auto;padding:0 20px}
header{padding:18px 0;position:sticky;top:0;background:#fff;z-index:100;box-shadow:0 2px 20px rgba(0,0,0,.06)}
.nav{display:flex;justify-content:space-between;align-items:center}
.logo{font-family:'Manrope',sans-serif;font-weight:800;font-size:24px;text-decoration:none;color:var(--dark);display:flex;align-items:center;gap:10px}
.logo span{color:var(--primary)}
.logo img{max-height:42px;width:auto;object-fit:contain}
.nav-actions{display:flex;align-items:center;gap:12px}
.btn{padding:11px 24px;border-radius:50px;text-decoration:none;font-weight:600;display:inline-flex;align-items:center;justify-content:center;line-height:1.2;font-size:14px;box-sizing:border-box;transition:.3s;vertical-align:middle}
.btn-primary{background:var(--primary);color:#fff} .btn-primary:hover{background:#088ec0;transform:translateY(-2px)}
.btn-outline{border:2px solid var(--dark);color:var(--dark)}
.btn-admin{background:#0F2233;color:#fff} .btn-admin:hover{background:#1e3a56;transform:translateY(-2px)}
.hero{background:var(--light);padding:70px 0 80px;display:flex;flex-wrap:wrap;align-items:center}
.hero-text{flex:1;min-width:320px} .hero-text h1{font-family:'Manrope',sans-serif;font-size:52px;line-height:1.1;font-weight:800} .hero-text h1 span{color:var(--primary)}
.hero-text p{margin:20px 0 30px;font-size:18px;color:#4a5a6a;line-height:1.6}
.hero-img{flex:1;min-width:320px;text-align:center} .hero-img img{width:90%;max-width:500px;border-radius:24px;box-shadow:0 20px 40px rgba(10,167,224,0.15)}
.badge{display:inline-block;background:#fff;border:1px solid #d6eef8;padding:6px 14px;border-radius:30px;color:var(--primary);font-weight:600;margin-bottom:18px;font-size:14px}
.services{padding:80px 0} .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:24px;margin-top:40px}
.card{background:#fff;border:1px solid #eef3f7;padding:30px;border-radius:18px;transition:.3s} .card:hover{box-shadow:0 15px 40px rgba(10,167,224,.15);transform:translateY(-5px)}
.card h3{margin:15px 0 10px}
.steps{background:var(--dark);color:#fff;padding:80px 0;border-radius:30px 30px 0 0}
.step-box{display:flex;gap:20px;margin-top:40px;flex-wrap:wrap} .step{flex:1;min-width:220px;background:rgba(255,255,255,.07);padding:24px;border-radius:16px}
.cta{background:var(--primary);color:#fff;text-align:center;padding:60px 20px}
footer{background:#0a1a28;color:#8aa0b3;padding:40px 0;text-align:center;font-size:14px}
@media(max-width:768px){.hero-text h1{font-size:38px}}
</style>
</head>
<body>

<header>
  <div class="container nav">
    <a href="{{ url('/') }}" class="logo">
      @if(class_exists(\Modules\Admin\Models\Setting::class) && \Modules\Admin\Models\Setting::hasCustom('site_logo'))
        <img src="{{ \Modules\Admin\Models\Setting::getLogoUrl() }}" alt="{{ \Modules\Admin\Models\Setting::getSiteName() }}">
      @else
        FreshGo<span>Laundry</span>
      @endif
    </a>
    <div class="nav-actions">
      {{-- <a href="{{ route('admin.login') }}" class="btn btn-admin">Admin Portal</a> --}}
      <a href="#book" class="btn btn-primary">Book Now</a>
    </div>
  </div>
</header>

<section class="hero">
  <div class="container" style="display:flex;flex-wrap:wrap;align-items:center;">
    <div class="hero-text">
      <div class="badge">✓ Free Collection & Delivery in 24h</div>
      <h1>Laundry & Dry Cleaning <br><span>Made Effortless</span></h1>
      <p>We collect, clean and deliver your clothes within 24 hours. Professional washing, ironing & dry cleaning for busy households and businesses.</p>
      <a href="#book" class="btn btn-primary">Schedule Free Pickup</a>
      <a href="#pricing" class="btn btn-outline" style="margin-left:10px">View Pricing</a>
      <p style="margin-top:18px;font-size:14px">⭐⭐⭐⭐⭐ 4.9/5 Rated by 2,300+ Happy Customers</p>
    </div>
    <div class="hero-img">
      <img src="https://images.unsplash.com/photo-1582735689369-4fe89db7114c?w=800" alt="Laundry Service">
    </div>
  </div>
</section>

<section class="services container">
  <center><h2 style="font-family:Manrope;font-size:36px;font-weight:800">Our Services</h2><p style="color:#6b7d8e;margin-top:10px">Everything you need to stay fresh</p></center>
  <div class="grid">
    <div class="card"><h2>👕</h2><h3>Wash & Fold</h3><p>Shirts, t-shirts, trousers washed at 30°-60°, folded and ready to wear.</p></div>
    <div class="card"><h2>👔</h2><h3>Dry Cleaning</h3><p>Suits, dresses, abayas & delicate wear with eco-friendly solvents.</p></div>
    <div class="card"><h2>🔥</h2><h3>Ironing Service</h3><p>Crisp, professional ironing with premium finish. Per item or by bag.</p></div>
    <div class="card"><h2>🛏️</h2><h3>Duvet & Bedding</h3><p>Large items, duvets, curtains and bedsheets deep cleaned.</p></div>
  </div>
</section>

<section class="steps">
  <div class="container">
    <center><h2 style="font-size:36px;font-family:Manrope">How It Works</h2></center>
    <div class="step-box">
      <div class="step"><h3>1. Book Online</h3><p>Choose your collection time in 60 seconds.</p></div>
      <div class="step"><h3>2. We Collect</h3><p>Our driver collects from your doorstep - free.</p></div>
      <div class="step"><h3>3. We Clean</h3><p>Professional cleaning in our modern facility.</p></div>
      <div class="step"><h3>4. We Deliver</h3><p>Fresh clothes delivered back in 24 hours.</p></div>
    </div>
  </div>
</section>

<section id="pricing" class="services container">
  <center><h2 style="font-family:Manrope;font-size:36px;font-weight:800">Simple Pricing</h2></center>
  <div class="grid" style="margin-top:30px">
    <div class="card" style="text-align:center"><h3>Wash & Fold Bag</h3><h1 style="font-size:42px;color:var(--primary);margin:15px 0">৳350</h1><p>6kg bag - perfect for weekly wash</p><br><a href="#book" class="btn btn-primary">Book Now</a></div>
    <div class="card" style="text-align:center;border:2px solid var(--primary)"><h3>⭐ Most Popular</h3><h3>Shirts - Ironed</h3><h1 style="font-size:42px;color:var(--primary);margin:15px 0">৳45</h1><p>Per shirt, washed & ironed</p><br><a href="#book" class="btn btn-primary">Book Now</a></div>
    <div class="card" style="text-align:center"><h3>Dry Cleaning</h3><h1 style="font-size:42px;color:var(--primary);margin:15px 0">From ৳150</h1><p>Suits, dresses, abayas & panjabis</p><br><a href="#book" class="btn btn-primary">Book Now</a></div>
  </div>
</section>

<section id="book" class="cta">
  <h2 style="font-family:Manrope;font-size:38px">Ready for Fresh Clothes?</h2>
  <p style="margin:15px 0 25px;font-size:18px">Book your free collection today. No subscription needed.</p>
  <a href="https://wa.me/8801700000000?text=Hi%20-%20I%20want%20to%20book%20a%20laundry%20collection" target="_blank" class="btn" style="background:#fff;color:var(--primary)">WhatsApp Us Now</a>
  <p style="margin-top:20px">Or Call: +880 1700-000000 | info@freshgolaundry.com</p>
</section>

<footer>
  <div class="container">
    <p><strong style="color:#fff">{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getSiteName() : 'FreshGo Laundry' }}</strong> - Freshness in every thread.<br>{{ class_exists(\Modules\Admin\Models\Setting::class) ? \Modules\Admin\Models\Setting::getFooterText() : '© 2026 FreshGo Laundry. All Rights Reserved.' }}</p>
  </div>
</footer>

</body>
</html>
