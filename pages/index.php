<?php
$title = 'StudyBuddy APIIT - Home';
include __DIR__ . '/header.php';
?>
    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-study-light to-white py-20">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-4xl md:text-6xl font-bold text-study-primary mb-6">
                Your Academic Success Partner
            </h1>
            <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                Access premium study materials, connect with top students, and excel in your academic journey at APIIT. 
                Browse notes, guides, and resources from successful students.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="materials.php" class="px-8 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                    Browse Materials
                </a>
                <a href="login.php?tab=register" class="px-8 py-3 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
                    Join Community
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-study-primary mb-12">Why Choose StudyBuddy?</h2>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-accent/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-book text-2xl text-primary"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Premium Resources</h3>
                    <p class="text-gray-600">Access high-quality study materials, notes, and guides created by top-performing students.</p>
                </div>
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-accent/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-2xl text-primary"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Student Community</h3>
                    <p class="text-gray-600">Connect with fellow APIIT students, share knowledge, and collaborate on projects.</p>
                </div>
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-accent/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-trophy text-2xl text-primary"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3">Academic Success</h3>
                    <p class="text-gray-600">Boost your grades with proven study strategies and resources from successful alumni.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-primary py-16">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">Ready to Excel in Your Studies?</h2>
            <p class="text-xl text-white/90 mb-8">Join thousands of APIIT students who are already using StudyBuddy to achieve academic success.</p>
            <a href="login.php?tab=register" class="px-8 py-3 bg-white text-primary rounded-lg hover:bg-gray-100 transition-colors font-semibold">
                Get Started Today
            </a>
        </div>
    </section>

    <?php include "footer.php"; ?>

    <script src="js/main.js"></script>
</body>
</html>