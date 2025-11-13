// 等待整个 HTML 页面加载完毕后再执行脚本
document.addEventListener('DOMContentLoaded', () => {

/*
    =========================================
    区块 2: 用户口碑 (Testimonials) 轮播 (无限 + 居中模式)
    =========================================
    */
    const testimonialSection = document.querySelector('.testimonials-section');
    if (testimonialSection) {
        const trackContainer = testimonialSection.querySelector('.carousel-track-container');
        const track = testimonialSection.querySelector('.carousel-track');
        let slides = Array.from(track.children);
        const nextButton = testimonialSection.querySelector('.arrow-right');
        const prevButton = testimonialSection.querySelector('.arrow-left');

        // 检查是否有足够的卡片来执行轮播
        if (slides.length > 0) {
            
            // --- 1. 克隆逻辑 ---
            // 我们需要克隆卡片来实现无缝循环。克隆2个看起来最自然。
            const clonesCount = 2;
            const slideCount = slides.length; // 真实的卡片数量

            // 从末尾克隆2个，加到开头
            for (let i = 0; i < clonesCount; i++) {
                const clone = slides[slideCount - 1 - i].cloneNode(true);
                track.insertBefore(clone, slides[0]);
            }
            
            // 从开头克隆2个，加到末尾
            for (let i = 0; i < clonesCount; i++) {
                const clone = slides[i].cloneNode(true);
                track.appendChild(clone);
            }

            // 更新 slides 列表 (现在包含了克隆体)
            slides = Array.from(track.children);
            
            /* * 关键：设置初始索引
             * 真实的卡片现在从 clonesCount (索引 2) 开始
             * 真实卡片 1 在索引 2 (clonesCount)
             * 真实卡片 2 在索引 3 (clonesCount + 1)
             *
             * 你希望默认高亮第二个卡片, 所以我们的 currentIndex 设为 (clonesCount + 1)
             */
            let currentIndex = clonesCount + 1; // 默认高亮真实卡片 2 (索引 3)

            // 如果总共只有 1 张真实卡片, 索引回退到 clonesCount
            if (slideCount === 1) {
                currentIndex = clonesCount;
            }

            let isSliding = false;
            let slideWidthWithMargins = 0;
            let initialOffset = 0;
            const slideDuration = 500; // 必须匹配 CSS 的 0.5s

            // --- 2. 重新计算所有宽度的函数 ---
            const calculateMetrics = () => {
                const firstRealSlide = slides[clonesCount]; // 用真实的第一张卡片计算
                const slideStyle = window.getComputedStyle(firstRealSlide);
                const slideMargin = parseFloat(slideStyle.marginLeft) + parseFloat(slideStyle.marginRight);
                slideWidthWithMargins = firstRealSlide.offsetWidth + slideMargin;
                
                const containerWidth = trackContainer.offsetWidth;
                const slideElementWidth = firstRealSlide.offsetWidth;
                
                initialOffset = (containerWidth / 2) - (slideElementWidth / 2);
            };

            // --- 3. 更新卡片高亮样式 ---
            const updateSlideStyles = () => {
                slides.forEach((slide, index) => {
                    if (index === currentIndex) {
                        slide.classList.remove('faded');
                    } else {
                        slide.classList.add('faded');
                    }
                });
            };

            // --- 4. 移动轨道的函数 (支持无动画) ---
            const moveToSlide = (index, animate = true) => {
                if (!animate) {
                    track.style.transition = 'none';
                }
                
                const newTransformX = initialOffset - (slideWidthWithMargins * index);
                track.style.transform = 'translateX(' + newTransformX + 'px)';
                currentIndex = index;
                updateSlideStyles();

                if (!animate) {
                    setTimeout(() => {
                        track.style.transition = 'transform 0.5s ease';
                    }, 10);
                }
            };

            // --- 5. 事件监听器 (包含“跳跃”逻辑) ---
            nextButton.addEventListener('click', () => {
                if (isSliding) return;
                isSliding = true;

                moveToSlide(currentIndex + 1);

                // 检查是否滑到了“末尾”的克隆体 (第一个克隆体)
                if (currentIndex === clonesCount + slideCount) {
                    // (例如: 真实 1,2,3... 索引 2,3,4... 克隆体 1 在索引 5)
                    // (currentIndex 变成了 5, 5 === 2 + 3)
                    setTimeout(() => {
                        // 跳回“真实”的第一张卡片 (索引 2)
                        moveToSlide(clonesCount, false); 
                        isSliding = false;
                    }, slideDuration);
                } else {
                    setTimeout(() => {
                        isSliding = false;
                    }, slideDuration);
                }
            });

            prevButton.addEventListener('click', () => {
                if (isSliding) return;
                isSliding = true;

                moveToSlide(currentIndex - 1);

                // 检查是否滑到了“开头”的克隆体 (最后一个克隆体)
                if (currentIndex === clonesCount - 1) {
                    // (例如: 索引 1)
                    setTimeout(() => {
                        // 跳回“真实”的最后一张卡片 (索引 2 + 3 - 1 = 4)
                        moveToSlide(clonesCount + slideCount - 1, false); 
                        isSliding = false;
                    }, slideDuration);
                } else {
                    setTimeout(() => {
                        isSliding = false;
                    }, slideDuration);
                }
            });

            // --- 6. 初始启动 ---
            calculateMetrics(); 
            // 页面加载时, 立即跳到我们设定的初始卡片 (无动画)
            moveToSlide(currentIndex, false); 

            // --- 7. 窗口大小改变时，重新计算并居中 ---
            window.addEventListener('resize', () => {
                calculateMetrics();
                moveToSlide(currentIndex, false); // 重新居中当前卡片 (无动画)
            });
        }
    }

    /*
    =========================================
    区块 4: 广告 (Ads) 轮播
    =========================================
    */
    const adsSection = document.querySelector('.ads-carousel-section');
    if (adsSection) {
        const track = adsSection.querySelector('.carousel-track');
        const slides = Array.from(track.children);
        const nextButton = adsSection.querySelector('.arrow-right');
        const prevButton = adsSection.querySelector('.arrow-left');

        // * 关键：计算距离 *
        // 这个轮播器每次只移动 1 个 slide，且 slide 宽度是 100%
        // 所以我们只需要知道容器的宽度
        const slideWidth = track.getBoundingClientRect().width;

        let currentIndex = 0;

        // 移动轨道的函数
        const moveToSlide = (index) => {
            track.style.transform = 'translateX(-' + (slideWidth * index) + 'px)';
            currentIndex = index;
        };

        // 点击“向右”按钮
        nextButton.addEventListener('click', () => {
            let nextIndex = currentIndex + 1;
            if (nextIndex >= slides.length) {
                nextIndex = 0; // 循环
            }
            moveToSlide(nextIndex);
        });

        // 点击“向左”按钮
        prevButton.addEventListener('click', () => {
            let prevIndex = currentIndex - 1;
            if (prevIndex < 0) {
                prevIndex = slides.length - 1; // 循环
            }
            moveToSlide(prevIndex);
        });
        
        // * 额外：如果窗口大小改变，我们需要重新计算 slideWidth
        window.addEventListener('resize', () => {
            // 重新计算宽度并重置位置
            const newSlideWidth = track.getBoundingClientRect().width;
            track.style.transform = 'translateX(-' + (newSlideWidth * currentIndex) + 'px)';
        });
    }

});