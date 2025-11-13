<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>搬家平台 - 首页</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php
        // 1. 包含你的头部文件 (head.php)
        include 'head.php'; 
    ?>

    <main>

        <section class="hero-section">
            <div class="slogan-container">
                <h2>Slogan (标语)</h2>
            </div>
        </section>

        <section class="testimonials-section">
            <h2 class="section-title">用户口碑</h2>
            
            <div class="carousel-container">
                <button class="arrow arrow-left">&lt;</button>
                <div class="carousel-track-container">
                    <div class="carousel-track">
                        <div class="testimonial-card">
                            <div class="avatar-placeholder user-avatar"></div>
                            <h3>用户姓名</h3>
                            <div class="review-box"><p>用户评价</p></div>
                        </div>
                        <div class="testimonial-card faded">
                            <div class="avatar-placeholder user-avatar"></div>
                            <h3>用户姓名</h3>
                            <div class="review-box"><p>用户评价</p></div>
                        </div>
                        <div class="testimonial-card faded">
                            <div class="avatar-placeholder user-avatar"></div>
                            <h3>用户姓名</h3>
                            <div class="review-box"><p>用户评价</p></div>
                        </div>
                    </div>
                </div>
                <button class="arrow arrow-right">&gt;</button>
            </div>
        </section>
        
        <section class="movers-section">
            <h2 class="section-title">优秀搬家工</h2>
            
            <div class="movers-grid">
                <div class="mover-card">
                    <div class="avatar-placeholder mover-avatar"></div>
                    <h3>搬家工名字</h3>
                    <div class="mover-info-box">
                        <p>好评星数</p>
                        <p>用户评价</p>
                    </div>
                </div>
                <div class="mover-card">
                    <div class="avatar-placeholder mover-avatar"></div>
                    <h3>搬家工名字</h3>
                    <div class="mover-info-box">
                        <p>好评星数</p>
                        <p>用户评价</p>
                    </div>
                </div>
                <div class="mover-card">
                    <div class="avatar-placeholder mover-avatar"></div>
                    <h3>搬家工名字</h3>
                    <div class="mover-info-box">
                        <p>好评星数</p>
                        <p>用户评价</p>
                    </div>
                </div>
                <div class="mover-card">
                    <div class="avatar-placeholder mover-avatar"></div>
                    <h3>搬家工名字</h3>
                    <div class="mover-info-box">
                        <p>好评星数</p>
                        <p>用户评价</p>
                    </div>
                </div>
                <div class="mover-card">
                    <div class="avatar-placeholder mover-avatar"></div>
                    <h3>搬家工名字</h3>
                    <div class="mover-info-box">
                        <p>好评星数</p>
                        <p>用户评价</p>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="ads-carousel-section">
            <h2 class="section-title">热门广告</h2>

            <div class="carousel-container">
                <button class="arrow arrow-left dark-arrow">&lt;</button>
                <div class="carousel-track-container">
                    <div class="carousel-track">
                        <div class="ad-carousel-card">
                            <div class="ad-card-image"><span>图片</span></div>
                            <div class="ad-card-info">
                                <h4>标题</h4>
                                <p>需要的搬家工人数: 3</p>
                                <p>出发/到达城市: 巴黎 -> 里昂</p>
                                <p>出发日期: 2025-12-01</p>
                                <p>描述: 只有几行描述...</p>
                            </div>
                        </div>
                        </div>
                </div>
                <button class="arrow arrow-right dark-arrow">&gt;</button>
            </div>
        </section>
        
    </main>

    <?php
        // 建议你创建一个 footer.php 来封装页脚
        // include 'footer.php'; 
    ?>
    
    <?php
        // include 'footer.php'; 
    ?>

    
    <script src="js/main.js"></script>
</body>
</html>