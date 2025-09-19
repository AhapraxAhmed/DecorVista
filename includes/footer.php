<!-- Footer -->
<footer class="bg-gray-200 text-black" data-scroll-section>
    <div class="max-w-7xl mx-auto py-12 px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
            
            <!-- Company Info -->
            <div class="col-span-1 md:col-span-2">
                <h3 class="font-heading text-2xl font-bold text-black mb-4">DecorVista</h3>
                <p class="text-gray-800 mb-6 leading-relaxed">
                    Transform your living spaces with our curated collection of interior design products and professional consultation services.
                </p>
                <div class="flex space-x-5 text-xl">
                    <a href="#" class="text-gray-900 hover:text-white transition-colors"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-gray-900 hover:text-white transition-colors"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-900 hover:text-white transition-colors"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-gray-900 hover:text-white transition-colors"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h4 class="font-semibold text-black mb-4 uppercase tracking-wide">Quick Links</h4>
                <ul class="space-y-2">
                    <li><a href="/about.php" class="hover:text-gray-800 transition-colors">About Us</a></li>
                    <li><a href="/products.php" class="hover:text-gray-800 transition-colors">Products</a></li>
                    <li><a href="/gallery.php" class="hover:text-gray-800 transition-colors">Gallery</a></li>
                    <li><a href="/designers.php" class="hover:text-gray-800 transition-colors">Designers</a></li>
                    <li><a href="/blog.php" class="hover:text-gray-800 transition-colors">Blog</a></li>
                </ul>
            </div>
            
            <!-- Support -->
            <div>
                <h4 class="font-semibold text-black mb-4 uppercase tracking-wide">Support</h4>
                <ul class="space-y-2">
                    <li><a href="/contact.php" class="hover:text-gray-800 transition-colors">Contact Us</a></li>
                    <li><a href="/faq.php" class="hover:text-gray-800 transition-colors">FAQ</a></li>
                    <li><a href="/privacy.php" class="hover:text-gray-800 transition-colors">Privacy Policy</a></li>
                    <li><a href="/terms.php" class="hover:text-gray-800 transition-colors">Terms of Service</a></li>
                    <li><a href="/sitemap.php" class="hover:text-gray-800 transition-colors">Sitemap</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Bottom Bar -->
        <div class="mt-12 pt-8 border-t border-gray-700 text-center md:text-left flex flex-col md:flex-row justify-between items-center">
            <p class="text-black text-sm">
                &copy; <?php echo date('Y'); ?> <span class="text-black font-bold">DecorVista</span>. All rights reserved.
            </p>
            <p class="text-gray-400 text-sm mt-3 md:mt-0">
                Made with <i class="fas fa-heart text-red-500"></i> for beautiful homes
            </p>
        </div>
    </div>
</footer>

<!-- JavaScript -->
<script>
    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuButton = document.querySelector('.mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
                if (window.locomotiveScroll) {
                    setTimeout(() => window.locomotiveScroll.update(), 100);
                }
            });
        }
        
        updateCartCount();
    });
    
    // Cart functionality
    function updateCartCount() {
        fetch('/api/cart-count.php')
            .then(response => response.json())
            .then(data => {
                const cartCount = document.getElementById('cart-count');
                if (cartCount && data.count !== undefined) {
                    cartCount.textContent = data.count;
                    cartCount.style.display = data.count > 0 ? 'flex' : 'none';
                    if (window.locomotiveScroll) {
                        setTimeout(() => window.locomotiveScroll.update(), 100);
                    }
                }
            })
            .catch(error => console.error('Error updating cart count:', error));
    }
    
    function addToCart(productId, quantity = 1) {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', quantity);
        formData.append('csrf_token', '<?php echo htmlspecialchars(generateCSRFToken()); ?>');
        
        fetch('/api/add-to-cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartCount();
                showNotification('Product added to cart!', 'success');
            } else {
                showNotification(data.message || 'Error adding product to cart', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Error adding product to cart', 'error');
        });
    }
    
    // Notification system
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-20 right-4 z-50 p-4 rounded-lg shadow-lg bg-white/90 backdrop-blur-lg border ${
            type === 'success' ? 'text-green-800 border-green-300' :
            type === 'error' ? 'text-red-800 border-red-300' :
            'text-gray-800 border-gray-200'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        if (window.locomotiveScroll) {
            setTimeout(() => window.locomotiveScroll.update(), 100);
        }
        
        setTimeout(() => {
            notification.remove();
            if (window.locomotiveScroll) {
                setTimeout(() => window.locomotiveScroll.update(), 100);
            }
        }, 3000);
    }
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const target = document.querySelector(targetId);
            if (target && window.locomotiveScroll) {
                window.locomotiveScroll.scrollTo(target);
            } else if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
</script>
</body>
</html>
