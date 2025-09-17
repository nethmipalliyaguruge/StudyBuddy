<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyBuddy APIIT - Your Academic Resource Marketplace</title>
    <meta name="description" content="Access premium study materials, connect with top students, and excel in your academic journey at APIIT. Browse notes, guides, and resources from successful students.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#006644',
                        'primary-foreground': '#ffffff',
                        secondary: '#f0f9f5',
                        'secondary-foreground': '#006644',
                        accent: '#4ade80',
                        'accent-foreground': '#006644',
                        muted: '#f1f5f9',
                        'muted-foreground': '#64748b',
                        background: '#ffffff',
                        foreground: '#0f172a',
                        card: '#ffffff',
                        'card-foreground': '#0f172a',
                        border: '#e2e8f0',
                        input: '#e2e8f0',
                        ring: '#006644',
                        'study-primary': '#006644',
                        'study-accent': '#4ade80',
                        'study-light': '#f0f9f5',
                        'study-muted': '#d1d5db'
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-background text-foreground">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <nav class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <a href="index.php" class="flex items-center space-x-2">
                        <i class="fas fa-graduation-cap text-2xl text-primary"></i>
                        <h1 class="text-xl font-bold text-primary">StudyBuddy APIIT</h1>
                    </a>
                </div>
                <div class="hidden md:flex space-x-6">
                    <a href="index.php" class="text-primary hover:text-accent transition-colors">Home</a>
                    <a href="materials.php" class="text-gray-600 hover:text-primary transition-colors">Study Materials</a>
                    <!-- <a href="about.php" class="text-gray-600 hover:text-primary transition-colors">About</a>
                    <a href="contact.php" class="text-gray-600 hover:text-primary transition-colors">Contact</a> -->
                </div>
                <div class="flex space-x-4">
                    <a href="./login.php" class="px-4 py-2 text-primary border border-primary rounded-md hover:bg-primary hover:text-white transition-colors">Login</a>
                    <a href="./login.php#registerForm" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/90 transition-colors">Register</a>
                </div>
                <!-- Mobile menu button -->
                <button id="mobile-menu-btn" class="md:hidden text-gray-600">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
            <!-- Mobile menu -->
            <div id="mobile-menu" class="hidden md:hidden mt-4 space-y-2">
                <a href="index.php" class="block py-2 text-primary">Home</a>
                <a href="materials.php" class="block py-2 text-gray-600">Study Materials</a>
                <a href="about.php" class="block py-2 text-gray-600">About</a>
                <a href="contact.php" class="block py-2 text-gray-600">Contact</a>
            </div>
        </nav>
    </header>

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
                <a href="./login.php#registerForm" class="px-8 py-3 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors">
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
            <a href="./login.php#registerForm" class="px-8 py-3 bg-white text-primary rounded-lg hover:bg-gray-100 transition-colors font-semibold">
                Get Started Today
            </a>
        </div>
    </section>

    <?php include "footer.php"; ?>

    <script src="js/main.js"></script>
</body>
</html>