<?php
// Review form component - can be included in product and designer pages
$target_type = $target_type ?? 'product'; // 'product' or 'designer'
$target_id = $target_id ?? 0;
$target_name = $target_name ?? '';
?>

<div class="glass-card p-6 mt-8">
    <h3 class="text-xl font-bold text-white mb-4">Write a Review</h3>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <form id="reviewForm" class="space-y-4">
            <div>
                <label class="block text-white mb-2">Rating *</label>
                <div class="flex gap-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" onclick="setRating(<?php echo $i; ?>)" 
                                class="rating-star text-2xl text-gray-400 hover:text-yellow-400 transition-colors">
                            <i class="fas fa-star"></i>
                        </button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" id="rating" name="rating" value="">
            </div>
            
            <div>
                <label class="block text-white mb-2">Your Review *</label>
                <textarea name="comment" rows="4" required 
                          placeholder="Share your experience with <?php echo htmlspecialchars($target_name); ?>..."
                          class="w-full px-4 py-3 bg-black/30 border border-purple-500/30 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:border-purple-400"></textarea>
            </div>
            
            <button type="submit" class="glass-button">
                <i class="fas fa-star mr-2"></i>Submit Review
            </button>
        </form>
    <?php else: ?>
        <div class="text-center py-8">
            <p class="text-gray-400 mb-4">Please log in to write a review</p>
            <a href="login.php" class="glass-button">
                <i class="fas fa-sign-in-alt mr-2"></i>Log In
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
let selectedRating = 0;

function setRating(rating) {
    selectedRating = rating;
    document.getElementById('rating').value = rating;
    
    // Update star display
    const stars = document.querySelectorAll('.rating-star');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.remove('text-gray-400');
            star.classList.add('text-yellow-400');
        } else {
            star.classList.remove('text-yellow-400');
            star.classList.add('text-gray-400');
        }
    });
}

document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (selectedRating === 0) {
        alert('Please select a rating');
        return;
    }
    
    const formData = new FormData(this);
    formData.append('<?php echo $target_type; ?>_id', '<?php echo $target_id; ?>');
    
    fetch('/api/submit-review.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'Failed to submit review');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while submitting your review');
    });
});
</script>
