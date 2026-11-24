<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Butajira Etete Super Market</title>
<!-- FontAwesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;}
body{font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;background:#f4f9f5;color:#18352a;}

/* Header & Logo */
header{background:rgba(255, 255, 255, 0.95);backdrop-filter:blur(10px);position:sticky;top:0;z-index:1000;padding:15px 7%;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;box-shadow:0 4px 20px rgba(0,0,0,0.06);transition: all 0.3s ease;}
.logo{font-size:20px;font-weight:800;color:#147a43;letter-spacing:0.5px;text-decoration:none;display:flex;align-items:center;gap:8px;}
.logo span{color:#ffc857;}

/* All 6 Navigation Buttons Styled Uniformly */
nav{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
nav a{
    text-decoration:none;
    color:white;
    font-weight:600;
    font-size:14px;
    padding:10px 18px;
    border-radius:25px;
    background: linear-gradient(135deg, #168b45, #0e542e);
    box-shadow: 0 4px 12px rgba(22,139,69,0.25);
    transition: all 0.25s ease;
}
nav a:hover{
    background: linear-gradient(135deg, #126c36, #0a3d21);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(22,139,69,0.35);
}

/* Hero Section with Background Image (etete_store.jpg ወይም ሊንክ) */
.hero{
    min-height:600px;
    padding:80px 7%;
    display:flex;
    align-items:center;
    color:white;
    background:linear-gradient(135deg,rgba(8,50,29,0.88),rgba(8,50,29,0.65)), url('assets/images/etete_store.jpg') center/cover no-repeat;
}
.hero-content{max-width:650px}
.hero h1{font-size:52px;margin:20px 0;line-height:1.2;font-weight:800;}
.hero h1 span{color:#ffc857}
.hero p{font-size:18px;line-height:1.8;color:#e2ece5;margin-bottom:10px;}

.buttons{display:flex;gap:18px;flex-wrap:wrap;margin-top:30px}

/* Golden Accent Button */
.btn {
    position: relative;
    padding: 15px 32px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 700;
    font-size: 15px;
    letter-spacing: 0.5px;
    background: linear-gradient(135deg, #ffc857, #f3b73e);
    color: #18352a;
    box-shadow: 0 6px 20px rgba(255,200,87,0.35);
    transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
}

.btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(255,200,87,0.5);
    background: linear-gradient(135deg, #ffd375, #eca824);
}

.btn-outline {
    background: transparent;
    border: 2px solid rgba(255, 255, 255, 0.8);
    color: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    backdrop-filter: blur(5px);
}

.btn-outline:hover {
    background: white;
    color: #18352a;
    border-color: white;
    box-shadow: 0 8px 20px rgba(255,255,255,0.3);
    transform: translateY(-3px);
}

.section{padding:80px 7%;text-align:center}
.section h2{font-size:36px;margin:15px 0;color:#103d27;font-weight:700;}
.section p{color:#556b5e;line-height:1.8;max-width:750px;margin:0 auto;font-size:16px;}

.cards{display:grid;grid-template-columns:repeat(3,1fr);gap:25px;margin-top:45px}
.card{background:white;padding:35px 25px;border-radius:14px;box-shadow:0 6px 25px rgba(0,0,0,0.04);border:1px solid #e5efe8;transition: transform 0.3s ease, box-shadow 0.3s ease;}
.card:hover{transform: translateY(-7px);box-shadow:0 12px 30px rgba(0,0,0,0.08);}
.card h1{font-size:40px;margin-bottom:10px;}
.card h3{color:#147a43;margin:15px 0;font-size:20px;}
.card p{font-size:15px;color:#64776b;}

.contact{background:#eaf5ed;border-top:1px solid #d5eada;border-bottom:1px solid #d5eada;}
.contact a{color:#147a43;font-weight:bold;text-decoration:none;transition: color 0.2s;}
.contact a:hover{text-decoration:underline;color:#0e542e;}

footer{background:#103d27;color:white;text-align:center;padding:35px 20px;font-size:15px;}
footer p{margin:6px 0;color:#bcd2c4;}

@media(max-width:850px){
 header{flex-direction:column;align-items:center;padding:15px 5%;}
 nav{justify-content:center;gap:8px;margin-top:10px;}
 .hero{text-align:center;justify-content:center;padding:60px 5%;}
 .hero-content{max-width:100%;}
 .buttons{justify-content:center;}
 .cards{grid-template-columns:1fr}
 .section{padding:50px 5%}
}
</style>
</head>

<body>

<header>
    <a href="index.php" class="logo">
        <i class="fa-solid fa-store" style="color: #168b45;"></i> 
        Butajira <span>Etete</span> Super Market
    </a>
    <nav>
        <a href="index.php">Home</a>
        <a href="#about">About Us</a>
        <a href="#contact">Contact Us</a>
        <a href="#location">Location</a>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
    </nav>
</header>

<section class="hero">
    <div class="hero-content">
        <h1>Fresh Choices.<br>Better <span>Living.</span></h1>
        <p>
            Welcome to Butajira Etete Super Market.
            Find quality groceries and everyday products
            at affordable prices. Enjoy convenient online shopping.
        </p>
        <div class="buttons">
            <a href="login.php" class="btn">Start Shopping</a>
            <a href="register.php" class="btn btn-outline">Create Account</a>
        </div>
    </div>
</section>

<section class="section">
    <h2>Why Choose Us?</h2>
    <p>We provide the best shopping experience with top-tier convenience.</p>
    <div class="cards">
        <div class="card">
            <h1>🥬</h1>
            <h3>Quality Products</h3>
            <p>Find everyday products for your home and family.</p>
        </div>
        <div class="card">
            <h1>💰</h1>
            <h3>Affordable Prices</h3>
            <p>Shop for your daily needs at reasonable prices.</p>
        </div>
        <div class="card">
            <h1>🛒</h1>
            <h3>Easy Online Shopping</h3>
            <p>Browse products and place orders conveniently.</p>
        </div>
    </div>
</section>

<section class="section" id="about">
    <h2>About Us</h2>
    <p>
        Butajira Etete Super Market is committed to providing
        customers with quality everyday products and convenient
        shopping services. Our online system allows customers
        to browse products, add items to their cart, place orders
        and manage their purchases easily.
    </p>
</section>

<section class="section contact" id="contact">
    <h2>Contact Us</h2>
    <div class="cards">
        <div class="card">
            <h1>📞</h1>
            <h3>Phone Number</h3>
            <p><a href="tel:0934628111">0934628111</a></p>
        </div>

        <div class="card" id="location">
            <h1>📍</h1>
            <h3>Our Location</h3>
            <p>Butajira, Ethiopia</p>
            <p style="margin-top: 10px;">
                <a target="_blank" rel="noopener"
                   href="https://www.google.com/maps/search/?api=1&query=Butajira%2C+Ethiopia">
                   View on Google Maps
                </a>
            </p>
        </div>

        <div class="card">
            <h1>🏪</h1>
            <h3>Our Business</h3>
            <p>Butajira Etete Super Market</p>
        </div>
    </div>
</section>

<section class="section">
    <h2>Ready to Start Shopping?</h2>
    <p>Create a customer account and enjoy convenient online shopping.</p>
    <br>
    <a href="register.php" class="btn">Register Now</a>
</section>

<footer>
    <p>© <?php echo date("Y"); ?> Butajira Etete Super Market[cite: 1]</p>
    <p>Butajira, Ethiopia | Phone: 0934628111[cite: 1]</p>
</footer>

</body>
</html>