<?php
// Mock data for calls table
$calls = [
    ["subject" => "Get more information on the proposed deal", "related" => "A.G. Parr PLC", "date" => "2026-11-09 17:30", "accept" => "Accepted", "status" => "Planned"],
    ["subject" => "Bad time, will call back", "related" => "Draft Diversified Energy Inc", "date" => "2027-02-10 12:00", "accept" => "Accepted", "status" => "Planned"],
    ["subject" => "Discuss review process", "related" => "Sandeon Consolidation Corp", "date" => "2027-01-26 17:00", "accept" => "Accepted", "status" => "Planned"],
    ["subject" => "Get more information on the proposed deal", "related" => "Kings Royalty Trust", "date" => "2027-06-27 14:15", "accept" => "Accepted", "status" => "Planned"],
    ["subject" => "Get more information on the proposed deal", "related" => "Constrata Trust LLC", "date" => "2027-08-25 17:45", "accept" => "Accepted", "status" => "Planned"]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CRMPro - Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- App Container -->
    <div class="app-container">

        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <h2>CRM<span class="gold-text">Pro</span></h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="#"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li class="active"><a href="#"><i class="fa-solid fa-user-group"></i> Customers</a></li>
                <li><a href="#"><i class="fa-solid fa-address-book"></i> Leads</a></li>
                <li><a href="#"><i class="fa-solid fa-building"></i> Companies</a></li>
                <li><a href="#"><i class="fa-solid fa-list-check"></i> Tasks</a></li>
                <li><a href="#"><i class="fa-solid fa-calendar-days"></i> Calendar</a></li>
                <li><a href="#"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
                <li><a href="#"><i class="fa-solid fa-gear"></i> Settings</a></li>
            </ul>
            <div class="sidebar-footer">
                <small>CRMPro &copy; 2024</small>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="main-content">

            <!-- Top Navbar -->
            <header class="top-navbar">
                <div class="search-bar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search customers, companies, or deals...">
                </div>
                <div class="user-nav">
                    <button class="icon-btn"><i class="fa-regular fa-bell"></i></button>
                    <div class="user-profile">
                        <span class="avatar">AC</span>
                        <div class="user-info">
                            <span class="name">Alex Carter</span>
                            <span class="role">Sales Manager</span>
                        </div>
                        <i class="fa-solid fa-chevron-down"></i>
                    </div>
                </div>
            </header>

            <!-- Dashboard Action Bar -->
            <div class="dashboard-action-bar">
                <div class="dashboard-title">SUITECRM DASHBOARD</div>
                <div class="dropdown-container">
                    <button class="action-dropdown-btn" onclick="toggleDropdown()">
                        Action <i class="fa-solid fa-chevron-down"></i>
                    </button>
                    <div id="actionDropdown" class="dropdown-menu">
                        <a href="#">Add Dashlet</a>
                        <a href="#">Add Tab</a>
                        <a href="#">Edit Tabs</a>
                    </div>
                </div>
            </div>

            <!-- Dashboard Grid Layout -->
            <div class="dashboard-grid">
                
                <!-- Left Column: My Calls Dashlet -->
                <div class="dashlet">
                    <div class="dashlet-header">
                        <h3><i class="fa-solid fa-phone"></i> My Calls</h3>
                        <div class="dashlet-tools">
                            <button><i class="fa-solid fa-gear"></i></button>
                            <button><i class="fa-solid fa-xmark"></i></button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="crm-table">
                            <thead>
                                <tr>
                                    <th>Close</th>
                                    <th>Subject <i class="fa-solid fa-sort"></i></th>
                                    <th>Related to</th>
                                    <th>Start Date <i class="fa-solid fa-sort"></i></th>
                                    <th>Accept?</th>
                                    <th>Status <i class="fa-solid fa-sort"></i></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($calls as $call): ?>
                                <tr>
                                    <td><button class="row-close-btn">&times;</button></td>
                                    <td><a href="#" class="table-link"><?= htmlspecialchars($call['subject']) ?></a></td>
                                    <td><a href="#" class="table-link"><?= htmlspecialchars($call['related']) ?></a></td>
                                    <td><?= htmlspecialchars($call['date']) ?></td>
                                    <td><?= htmlspecialchars($call['accept']) ?></td>
                                    <td><?= htmlspecialchars($call['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="pagination-footer">
                        <button>&lt;&lt;</button>
                        <button>&lt;</button>
                        <span>(1 - 5 of 7)</span>
                        <button>&gt;</button>
                        <button>&gt;&gt;</button>
                    </div>
                </div>

                <!-- Right Column: My Activity Stream Dashlet -->
                <div class="dashlet">
                    <div class="dashlet-header">
                        <h3><i class="fa-regular fa-bell"></i> My Activity Stream</h3>
                        <div class="dashlet-tools">
                            <button><i class="fa-solid fa-gear"></i></button>
                            <button><i class="fa-solid fa-xmark"></i></button>
                        </div>
                    </div>
                    <div class="activity-stream-body">
                        <div class="activity-item">
                            <div class="activity-avatar"><i class="fa-solid fa-user"></i></div>
                            <div class="activity-content">
                                <strong>Status Update</strong>
                                <span class="time">0h 10 Minutes ago</span>
                                <p>Will Westin created a new opportunity TEST with <a href="#">B.C. Investing International</a> for amount $200,000.00</p>
                                <div class="activity-actions">
                                    <button class="act-btn"><i class="fa-solid fa-thumbs-up"></i></button>
                                    <button class="act-btn"><i class="fa-solid fa-reply"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-avatar img-avatar">
                                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=100" alt="User">
                            </div>
                            <div class="activity-content">
                                <strong>Task Complete</strong>
                                <span class="time">0h 10 Minutes ago</span>
                                <p>Will Westin created a new contact TEST TEST</p>
                                <div class="activity-actions">
                                    <button class="act-btn"><i class="fa-solid fa-thumbs-up"></i></button>
                                    <button class="act-btn"><i class="fa-solid fa-reply"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            document.getElementById("actionDropdown").classList.toggle("show");
        }
        window.onclick = function(event) {
            if (!event.target.matches('.action-dropdown-btn') && !event.target.matches('.fa-chevron-down')) {
                var dropdowns = document.getElementsByClassName("dropdown-menu");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>
</body>
</html>