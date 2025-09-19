<?php
require_once 'config/config.php';
$pageTitle = 'Contact Us - DecorVista';
include 'includes/header.php';
?>

<!-- Locomotive Scroll Styles -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/locomotive-scroll@4.1.4/dist/locomotive-scroll.min.css" />

<div id="scroll-container" data-scroll-container class="bg-white text-black min-h-screen">

    <!-- Hero Section -->
    <section class="flex flex-col justify-center items-center px-6 mt-16" data-scroll-section>
        <h1 class="text-5xl md:text-7xl font-bold mb-6" data-scroll data-scroll-speed="2">
            Let’s <span class="text-gray-600">Connect</span>
        </h1>
        <p class="max-w-xl text-center text-lg text-gray-700" data-scroll data-scroll-speed="1">
            Have a project in mind? Drop us a message and let’s make your dream space come alive.
        </p>
    </section>

    <!-- Contact Form Section -->
    <section class="py-16 px-6" data-scroll-section>
        <div class="max-w-4xl mx-auto">
            <div class="mb-12 text-center">
                <h2 class="text-4xl font-bold mb-4" data-scroll data-scroll-speed="2">Get In Touch</h2>
                <p class="text-gray-600" data-scroll data-scroll-speed="1">
                    We’d love to hear from you. Fill out the form below and we’ll get back to you soon.
                </p>
            </div>

            <form method="POST" action="send_message.php" class="space-y-6" data-scroll>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block mb-2 font-medium">Name</label>
                        <input type="text" name="name" required
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-600 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block mb-2 font-medium">Email</label>
                        <input type="email" name="email" required
                               class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-600 focus:outline-none transition">
                    </div>
                </div>
                <div>
                    <label class="block mb-2 font-medium">Subject</label>
                    <input type="text" name="subject" required
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-600 focus:outline-none transition">
                </div>
                <div>
                    <label class="block mb-2 font-medium">Message</label>
                    <textarea name="message" rows="6" required
                              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-600 focus:outline-none transition"></textarea>
                </div>
                <div class="text-center">
                    <button type="submit" 
                            class="bg-gray-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-purple-700 transition transform hover:scale-105">
                        Send Message
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- Contact Info Section -->
    <section class="py-16 px-6 bg-gray-100" data-scroll-section>
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-3xl font-bold mb-8" data-scroll data-scroll-speed="2">Other Ways to Reach Us</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="p-6 bg-white shadow-lg rounded-lg hover:shadow-2xl transition" data-scroll>
                    <i class="fas fa-map-marker-alt text-3xl text-gray-600 mb-4"></i>
                    <h3 class="font-semibold text-lg mb-2">Our Office</h3>
                    <p class="text-gray-600">123 DecorVista Street, Interior City</p>
                </div>
                <div class="p-6 bg-white shadow-lg rounded-lg hover:shadow-2xl transition" data-scroll>
                    <i class="fas fa-envelope text-3xl text-gray-600 mb-4"></i>
                    <h3 class="font-semibold text-lg mb-2">Email Us</h3>
                    <p class="text-gray-600">contact@decorvista.com</p>
                </div>
                <div class="p-6 bg-white shadow-lg rounded-lg hover:shadow-2xl transition" data-scroll>
                    <i class="fas fa-phone text-3xl text-gray-600 mb-4"></i>
                    <h3 class="font-semibold text-lg mb-2">Call Us</h3>
                    <p class="text-gray-600">+1 (234) 567-890</p>
                </div>
            </div>
        </div>
    </section>

<!-- Footer Section (INSIDE scroll container) -->
<?php include 'includes/footer.php'; ?>

</div> <!-- end #scroll-container -->

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/locomotive-scroll@4.1.4/dist/locomotive-scroll.min.js"></script>
<script>
    window.locomotiveScroll = new LocomotiveScroll({
        el: document.querySelector('#scroll-container'),
        smooth: true,
        multiplier: 1.2,
        smartphone: { smooth: true },
        tablet: { smooth: true }
    });
</script>
