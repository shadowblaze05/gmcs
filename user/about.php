<?php
session_start();
require_once '../db.php';

/* Optional: restrict access to logged-in users */
if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>About the System</title>

    <style>
        /* ================================
   GLOBAL STYLES
================================ */
        * {
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #05060a, #0d1020);
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* ================================
   CONTAINER
================================ */
        .about-container {
            width: 900px;
            max-width: 95%;
            background: #0f1225;
            padding: 40px 45px;
            border-radius: 16px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.65);
        }

        /* ================================
   HEADINGS
================================ */
        .about-container h1 {
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
            letter-spacing: 0.8px;
        }

        .about-container h3 {
            margin-top: 30px;
            margin-bottom: 12px;
            color: #9aa2ff;
            font-size: 18px;
        }

        /* ================================
   TEXT
================================ */
        .about-container p {
            line-height: 1.7;
            font-size: 15px;
            color: #e1e3ff;
        }

        /* ================================
   FEATURE LIST
================================ */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-top: 15px;
        }

        .feature-box {
            background: #1a1d3a;
            padding: 16px 18px;
            border-radius: 10px;
            border-left: 4px solid #4f6cff;
        }

        .feature-box h4 {
            margin: 0 0 6px 0;
            font-size: 15px;
            color: #ffffff;
        }

        .feature-box p {
            margin: 0;
            font-size: 14px;
            color: #cfd2ff;
        }

        /* ================================
   FOOTER
================================ */
        .about-footer {
            text-align: center;
            margin-top: 35px;
            font-size: 13px;
            color: #aab0ff;
        }

        /* ================================
   RESPONSIVE
================================ */
        @media (max-width: 600px) {
            .about-container {
                padding: 28px 24px;
            }
        }
    </style>
</head>

<body>

    <div class="about-container">

        <h1>About the Game Collection System</h1>

        <p>
            The <strong>Game Collection System</strong> is a database-driven web application designed
            to manage user-owned games, in-game progress, cosmetic items, and player feedback.
            It allows users to track their game achievements, purchased skins, ranks, and reviews
            while ensuring data consistency, security, and efficient performance.
        </p>

        <h3>🎯 System Objectives</h3>
        <p>
            The primary objective of this system is to provide a centralized platform where users
            can record their gaming progress and experiences. The system demonstrates the practical
            application of database concepts such as normalization, relationships, transactions,
            indexing, and advanced SQL features.
        </p>

        <h3>⚙️ Core Features</h3>
        <div class="features">
            <div class="feature-box">
                <h4>User Game Management</h4>
                <p>Tracks games owned by users, including rank progression and purchased skins.</p>
            </div>

            <div class="feature-box">
                <h4>Review & Rating System</h4>
                <p>Allows users to submit reviews and ratings for games they have played.</p>
            </div>

            <div class="feature-box">
                <h4>Relational Database Design</h4>
                <p>Uses normalized tables with foreign keys to maintain data integrity.</p>
            </div>

            <div class="feature-box">
                <h4>Advanced SQL Usage</h4>
                <p>Implements subqueries, views, stored procedures, triggers, and functions.</p>
            </div>

            <div class="feature-box">
                <h4>Transaction Management</h4>
                <p>Ensures atomic operations using commit and rollback mechanisms.</p>
            </div>

            <div class="feature-box">
                <h4>Security & Optimization</h4>
                <p>Uses prepared statements and indexes to improve security and performance.</p>
            </div>
        </div>

        <h3>🧠 Technologies Used</h3>
        <p>
            This system is built using <strong>PHP</strong> for server-side processing,
            <strong>MySQL</strong> as the relational database management system, and
            <strong>HTML/CSS/JavaScript</strong> for the user interface. The database uses the
            InnoDB engine to support transactions and concurrency control.
        </p>

        <div class="about-footer">
            © <?= date('Y') ?> GMCS. All rights reserved.
        </div>

    </div>

</body>

</html>