<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>发布新广告 - 搬家平台</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php
        
        include 'head.php'; 
    ?>

    <main class="content-wrapper">
        
        <h1 class="page-title">发布新广告</h1>
        
        <form class="publish-ad-form" action="submit_ad.php" method="POST" enctype="multipart/form-data">
            
            <div class="form-left">
                <label for="ad-image-upload" class="file-upload-label">
                    <span>+ 上传图片</span>
                </label>
                <input type="file" id="ad-image-upload" name="ad_image" accept="image/*" hidden>
            </div>
            
            <div class="form-right">
                
                <div class="input-group">
                    <input type="text" name="departure_city" placeholder="出发城市" required>
                    <input type="text" name="arrival_city" placeholder="到达城市" required>
                </div>
                
                <select name="movers_count" required>
                    <option value="" disabled selected>需要的搬家工人数</option>
                    <option value="1">1 人</option>
                    <option value="2">2 人</option>
                    <option value="3">3 人</option>
                    <option value="4">4 人</option>
                    <option value="5+">5 人或更多</option>
                </select>
                
                <input type="text" name="departure_date" placeholder="出发日期" required onfocus="this.type='date'" onblur="if(!this.value) { this.type='text'; }">
                
                <textarea name="description" class="description-box" placeholder="描述 " rows="6"></textarea>
                
                <button type="submit" class="submit-btn">上传</button>
            </div>
            
        </form>
        
    </main>

</body>
</html>