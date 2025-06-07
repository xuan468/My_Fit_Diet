<?php
// Define modules and full paths
$modules = [
    'homepage' => ['member_homepage.php', 'admin_homepage.php', 'manager_homepage.php'],
    'summary' => ['member_report.php', 'manager_report.php'],
    'dietplan' => ['dietplans.php', 'Food_Knowledge_User.php', 'Food_Knowledge_Admin.php', 'Recipe_User.php', 'Recipe_Admin.php', 'Recipe_Details_User.php'],
    'challenges' => ['challenges.php', 'checkin.php', 'adminchallenges.php'],
    'schedule' => ['schedule.php', 'submit_schedule.php', 'submit_workout.php', 'weeklys.php'],
    'profile' => ['member.php','view-member.php'],
    'community' => ['community.php', 'view-member.php'],
    'feedback' => ['feedbacks.php'],
    'Manage Staff' => ['manage.php', 'staff_register.php'],
];

// Complete page path mapping
$breadcrumb_map = [
    'member_homepage.php' => ['/MyFitDiet/homepage/member_homepage.php', 'Homepage'],
    'admin_homepage.php' => ['/MyFitDiet/homepage/admin_homepage.php', 'Homepage'],
    'manager_homepage.php' => ['/MyFitDiet/homepage/manager_homepage.php', 'Homepage'],
    'member_report.php' => ['/MyFitDiet/report/member_report.php', 'Summary'],
    'manager_report.php' => ['/MyFitDiet/report/manager_report.php', 'Report'],
    'dietplans.php' => ['/MyFitDiet/Food_Information/dietplans.php', 'Diet Plan'],
    'challenges.php' => ['/MyFitDiet/challenges/challenges.php', 'Challenges'],
    'checkin.php' => ['/MyFitDiet/challenges/checkin.php', 'Check-in'],
    'adminchallenges.php' => ['/MyFitDiet/challenges/adminchallenges.php', 'Admin Challenges'],
    'schedule.php' => ['/MyFitDiet/schedules/schedule.php', 'Workout Schedule'],
    'submit_schedule.php' => ['/MyFitDiet/schedules/submit_schedule.php', 'Submit Schedule'],
    'submit_workout.php' => ['/MyFitDiet/schedules/submit_workout.php', 'Submit Workout'],
    'weeklys.php' => ['/MyFitDiet/schedules/weeklys.php', 'Weekly'],
    'community.php' => ['/MyFitDiet/community/community.php', 'Community'],
    'feedbacks.php' => ['/MyFitDiet/feedback/feedbacks.php', 'Feedback'],
    'member.php' => ['/MyFitDiet/profile/member/member.php', 'Profile'],
    'Food_Knowledge_User.php' => ['/MyFitDiet/Food_Information/Food_Knowledge_User.php', 'Food Knowledge'],
    'Food_Knowledge_Admin.php' => ['/MyFitDiet/Food_Information/Food_Knowledge_Admin.php', 'Food Knowledge'],
    'Recipe_User.php' => ['/MyFitDiet/Food_Information/Recipe_User.php', 'Recipe'],
    'Recipe_Admin.php' => ['/MyFitDiet/Food_Information/Recipe_Admin.php', 'Recipe'],
    'view-member.php' => ['/MyFitDiet/profile/member/view-member.php', 'View Other'],
    'Recipe_Details_User.php' => ['/MyFitDiet/Food_Information/Recipe_Details_User.php', 'Recipe Details']
];

// Get the current page
$current_page = basename($_SERVER['PHP_SELF']);

// Detect which module the current page belongs to
$current_module = null;
foreach ($modules as $module => $pages) {
    if (in_array($current_page, $pages)) {
        $current_module = $module;
        break;
    }
}

// Initialize breadcrumb if SESSION does not have it
if (!isset($_SESSION['breadcrumb'])) {
    $_SESSION['breadcrumb'] = [
        ['page' => 'member_homepage.php', 'path' => '/MyFitDiet/homepage/member_homepage.php', 'name' => 'Homepage', 'module' => 'homepage']
    ];
}

// Get the last visited page
$last_breadcrumb = end($_SESSION['breadcrumb']);
$last_module = $last_breadcrumb['module'] ?? null;

// If accessed via Navigation Bar, reset and keep only Homepage > Module
if ($current_module !== $last_module) {
    // Keep Homepage
    $_SESSION['breadcrumb'] = [
        ['page' => 'member_homepage.php', 'path' => '/MyFitDiet/homepage/member_homepage.php', 'name' => 'Homepage', 'module' => 'homepage']
    ];

    // Add the root page of the current module
    $module_root_page = $modules[$current_module][0]; // 获取模块的第一个页面
    if (isset($breadcrumb_map[$module_root_page])) {
        $_SESSION['breadcrumb'][] = [
            'page' => $module_root_page,
            'path' => $breadcrumb_map[$module_root_page][0],
            'name' => $breadcrumb_map[$module_root_page][1],
            'module' => $current_module
        ];
    }
}

// Check if the current page already exists in breadcrumb
$existing_index = array_search($current_page, array_column($_SESSION['breadcrumb'], 'page'));

// If the current page exists, remove the following pages
if ($existing_index !== false) {
    $_SESSION['breadcrumb'] = array_slice($_SESSION['breadcrumb'], 0, $existing_index + 1);
} else {
    // Add new page
    if (isset($breadcrumb_map[$current_page])) {
        $_SESSION['breadcrumb'][] = [
            'page' => $current_page,
            'path' => $breadcrumb_map[$current_page][0],
            'name' => $breadcrumb_map[$current_page][1],
            'module' => $current_module
        ];
    }
}

// Generate HTML
$breadcrumbs = [];
foreach ($_SESSION['breadcrumb'] as $item) {
    $breadcrumbs[] = '<a href="' . $item['path'] . '">' . $item['name'] . '</a>';
}

// Output breadcrumb
echo '<div class="breadcrumb">' . implode(' > ', $breadcrumbs) . '</div>';
?>

<style>
   .breadcrumb {
    font-size: 13px;
    padding: 0 15px 8px 15px;
    background: transparent;
    margin: 10px auto 0 !important;
    display: block !important;
    width: 100vw !important;
    text-align: left !important;
}

.fixed-left-breadcrumb {
    position: absolute !important;
    left: 20px !important;
    top: 60px !important;
    width: auto;
    z-index: 1000;
}

.fixed-right-breadcrumb {
    position: absolute !important;
    right: 20px !important;
    top: 60px !important;
    width: auto;
    text-align: right !important;
}

.center-breadcrumb {
    text-align: center !important;
    margin-left: auto !important;
    margin-right: auto !important;
    display: block !important;
}

.breadcrumb a {
    text-decoration: none;
    color: #007bff !important;
    font-weight: 500;
    transition: color 0.2s ease-in-out;
}

.breadcrumb a:hover {
    text-decoration: underline;
    color: #0056b3 !important;
}
</style>