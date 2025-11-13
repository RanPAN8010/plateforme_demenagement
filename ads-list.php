<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>广告列表 - 搬家平台</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php
        // 1. 包含你之前封装好的头部导航栏
        include 'head.php'; 
    ?>

    <main class="content-wrapper">
        
        <section class="filter-section">
            <input type="text" class="filter-input" placeholder="选出发城市 (城市/邮编)">
            <input type="text" class="filter-input" placeholder="选到达城市 (城市/邮编)">
            
            <input type="text" class="filter-input" placeholder="选出发时间" onfocus="this.type='date'" onblur="if(!this.value) { this.type='text'; }">
            <input type="text" class="filter-input" placeholder="选到达时间" onfocus="this.type='date'" onblur="if(!this.value) { this.type='text'; }">
        </section>
        
        <hr class="separator">
        
        <section class="ad-list-container">
            
            <article class="ad-item">
                <div class="ad-avatar"></div>
                <div class="ad-info">
                    <p>广告信息 1 (这里将显示数据库内容)</p>
                </div>
            </article>
            
            <article class="ad-item">
                <div class="ad-avatar"></div>
                <div class="ad-info">
                    <p>广告信息 2 (这里将显示数据库内容)</p>
                </div>
            </article>
            
            <article class="ad-item">
                <div class="ad-avatar"></div>
                <div class="ad-info">
                    <p>广告信息 3 (这里将显示数据库内容)</p>
                </div>
            </article>
            
            <article class="ad-item">
                <div class="ad-avatar"></div>
                <div class="ad-info">
                    <p>广告信息 4 (这里将显示数据库内容)</p>
                </div>
            </article>
            
        </section>
        
    </main>

</body>
</html>