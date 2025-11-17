<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Publier une annonce - HomeGo</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <?php
        // 引入导航栏
        include 'head.php'; 
    ?>

    <main class="content-wrapper">
        
        <h1 class="page-title">Publier une nouvelle annonce</h1>
        
        <form class="publish-ad-form" action="submit_ad.php" method="POST" enctype="multipart/form-data">
            
            <div class="form-left">
                <label for="ad-image-upload" class="file-upload-label">
                    <span>+ Ajouter une photo</span>
                </label>
                <input type="file" id="ad-image-upload" name="ad_image" accept="image/*" hidden>
            </div>
            
            <div class="form-right">
                
<div class="input-group">
                    <input type="text" id="dep-input" list="city-list" placeholder="Ville de départ (ex: Paris 75000)" required autocomplete="off">
                    <input type="hidden" name="departure_city" id="dep-hidden">

                    <input type="text" id="arr-input" list="city-list" placeholder="Ville d'arrivée (ex: Lyon 69000)" required autocomplete="off">
                    <input type="hidden" name="arrival_city" id="arr-hidden">
                </div>

                <datalist id="city-list">
                    </datalist>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const dataList = document.getElementById('city-list');
                        
                        // 1. 从后端获取城市数据
                        fetch('get_cities.php')
                            .then(response => response.json())
                            .then(cities => {
                                cities.forEach(city => {
                                    // 创建选项: <option value="Paris (75000)">
                                    const option = document.createElement('option');
                                    // 显示格式: 城市名 (邮编)
                                    option.value = `${city.nom_ville} (${city.code_postal})`; 
                                    dataList.appendChild(option);
                                });
                            });

                        // 2. 处理输入逻辑 (确保提交的是干净的城市名)
                        const setupInput = (inputId, hiddenId) => {
                            const input = document.getElementById(inputId);
                            const hidden = document.getElementById(hiddenId);

                            input.addEventListener('input', function() {
                                // 如果用户选了 "Paris (75000)", 我们只取 "Paris" 发送给后端
                                // 或者用户手打 "Bordeaux", 我们就发 "Bordeaux"
                                let val = this.value;
                                // 简单的正则：如果包含括号，去掉括号及里面的内容
                                let cleanName = val.split('(')[0].trim();
                                hidden.value = cleanName;
                            });
                            
                            // 初始化
                            input.addEventListener('change', function() {
                                let val = this.value;
                                let cleanName = val.split('(')[0].trim();
                                hidden.value = cleanName;
                            });
                        };

                        setupInput('dep-input', 'dep-hidden');
                        setupInput('arr-input', 'arr-hidden');
                    });
                </script>
                
                <select name="movers_count" required>
                    <option value="" disabled selected>Nombre de déménageurs requis</option>
                    <option value="1">1 personne</option>
                    <option value="2">2 personnes</option>
                    <option value="3">3 personnes</option>
                    <option value="4">4 personnes</option>
                    <option value="5+">5 personnes ou plus</option>
                </select>
                
                <input type="text" name="departure_date" placeholder="Date de départ" required onfocus="this.type='date'" onblur="if(!this.value) { this.type='text'; }">
                
                <textarea name="description" class="description-box" placeholder="Description (ex : piano, 5ème étage sans ascenseur, liste des meubles...)" rows="6"></textarea>
                
                <button type="submit" class="submit-btn">Publier</button>
            </div>
            
        </form>
        
    </main>

</body>
</html>