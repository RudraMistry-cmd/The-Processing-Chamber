<?php
// Navigation functions and components

// Get main navigation items
function getNavigationItems() {
    return [
        [
            'title' => 'Home',
            'url' => SITE_URL . '/index.php',
            'icon' => 'fas fa-home'
        ],
        [
            'title' => 'Products',
            'url' => SITE_URL . '/pages/products.php',
            'icon' => 'fas fa-box'
        ],
        [
            'title' => 'Categories',
            'url' => SITE_URL . '/pages/products.php',
            'icon' => 'fas fa-list'
        ],
        [
            'title' => 'About Us',
            'url' => '#',
            'icon' => 'fas fa-info-circle'
        ],
        [
            'title' => 'Contact',
            'url' => SITE_URL . '/pages/contact.php',
            'icon' => 'fas fa-phone'
        ]
    ];
}

// Get category navigation
function getCategoryNavigation() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Get user navigation items
function getUserNavigation() {
    if (isset($_SESSION['user_id'])) {
        return [
            [
                'title' => 'My Profile',
                'url' => SITE_URL . '/pages/profile.php',
                'icon' => 'fas fa-user'
            ],
            [
                'title' => 'My Orders',
                'url' => SITE_URL . '/pages/profile.php',
                'icon' => 'fas fa-shopping-bag'
            ],
            [
                'title' => 'Logout',
                'url' => SITE_URL . '/logout.php',
                'icon' => 'fas fa-sign-out-alt'
            ]
        ];
    } else {
        return [
            [
                'title' => 'Login',
                'url' => SITE_URL . '/pages/login.php',
                'icon' => 'fas fa-sign-in-alt'
            ],
            [
                'title' => 'Register',
                'url' => SITE_URL . '/pages/register.php',
                'icon' => 'fas fa-user-plus'
            ]
        ];
    }
}