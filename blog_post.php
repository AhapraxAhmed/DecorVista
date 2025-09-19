<?php
require_once 'config/config.php';

$post_id = (int)($_GET['id'] ?? 0);

if ($post_id <= 0) {
    header('Location: /blog.php');
    exit();
}

// Fetch the blog post
$stmt = $db->prepare("SELECT * FROM blog_posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    header('Location: /blog.php');
    exit();
}

// Optional: fetch author info (if you have an authors table)
$author_name = $post['author'] ?? 'Admin';

// Optional: fetch related posts (same category, excluding current)
$stmt = $db->prepare("SELECT id, title, image_url FROM blog_posts WHERE category = ? AND id != ? ORDER BY created_at DESC LIMIT 3");
$stmt->bind_param("si", $post['category'], $post_id);
$stmt->execute();
$related_posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$pageTitle = htmlspecialchars($post['title']) . ' - Blog - DecorVista';

include 'includes/header.php';
?>

<div data-scroll-container>
    <main data-scroll-section>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- Breadcrumb -->
            <nav class="mb-8 text-sm text-gray-600">
                <ol class="flex items-center space-x-2">
                    <li><a href="index.php" class="hover:text-black">Home</a></li>
                    <li><i class="fas fa-chevron-right text-xs"></i></li>
                    <li><a href="blog.php" class="hover:text-black">Blog</a></li>
                    <li><i class="fas fa-chevron-right text-xs"></i></li>
                    <li class="text-gray-900"><?php echo htmlspecialchars($post['title']); ?></li>
                </ol>
            </nav>

            <!-- Blog Post -->
            <article class="bg-white rounded-2xl p-8 shadow-sm mb-12">
                <h1 class="text-4xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($post['title']); ?></h1>

                <div class="flex flex-wrap text-sm text-gray-500 mb-6 space-x-4">
                    <span><i class="fas fa-calendar mr-1"></i><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                    <span><i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($author_name); ?></span>
                    <?php if (!empty($post['category'])): ?>
                        <span><i class="fas fa-tags mr-1"></i><?php echo htmlspecialchars(ucfirst($post['category'])); ?></span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($post['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($post['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($post['title']); ?>" 
                         class="mb-6 w-full rounded-lg">
                <?php endif; ?>

                <div class="prose max-w-none text-gray-700 mb-8">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>

                <a href="/blog.php" class="inline-block bg-gray-800 text-white px-6 py-3 rounded-lg hover:bg-gray-900 transition-all duration-300">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Blog
                </a>
            </article>

            <!-- Related Posts -->
            <?php if (!empty($related_posts)): ?>
                <section>
                    <h2 class="text-2xl font-bold mb-6">Related Posts</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php foreach ($related_posts as $related): ?>
                            <a href="/blog_post.php?id=<?php echo $related['id']; ?>" class="group block bg-white rounded-xl shadow-sm hover:shadow-lg transition-shadow duration-300 overflow-hidden">
                                <?php if (!empty($related['image_url'])): ?>
                                    <div class="aspect-video bg-gray-100 relative overflow-hidden">
                                        <img src="<?php echo htmlspecialchars($related['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($related['title']); ?>" 
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    </div>
                                <?php endif; ?>
                                <div class="p-4">
                                    <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($related['title']); ?></h3>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/locomotive-scroll@4.1.4/dist/locomotive-scroll.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const scroll = new LocomotiveScroll({
        el: document.querySelector("[data-scroll-container]"),
        smooth: true,
    });
});
</script>
