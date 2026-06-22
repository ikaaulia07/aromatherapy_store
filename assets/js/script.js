document.addEventListener('DOMContentLoaded', function () {
    // Mobile Navigation Toggle
    const toggleBtn = document.getElementById('js-navbar-toggle');
    const menu = document.getElementById('js-navbar-menu');
    
    if (toggleBtn && menu) {
        toggleBtn.addEventListener('click', function () {
            menu.classList.toggle('active');
            
            // Icon transformation (optional)
            const icon = toggleBtn.querySelector('i');
            if (icon) {
                if (menu.classList.contains('active')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });
    }

    // Auto-fade notifications after 4 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.6s ease';
            setTimeout(function () {
                alert.style.display = 'none';
            }, 600);
        }, 4000);
    });

    // --- 1. Scroll Reveal Animation for Customer views ---
    const revealElements = document.querySelectorAll('.product-card, .section-title, .subtitle, .hero-section h1, .hero-section p, .hero-section div');
    
    // Set initial styles for reveal elements
    revealElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.8s cubic-bezier(0.25, 1, 0.5, 1), transform 0.8s cubic-bezier(0.25, 1, 0.5, 1)';
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, 100);
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.05,
        rootMargin: '0px 0px -50px 0px'
    });

    revealElements.forEach(el => observer.observe(el));

    // --- 2. 3D Mouse Tilt effect for Product Cards (Disabled per user request) ---
    /*
    const cards = document.querySelectorAll('.product-card');
    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left; // x position inside element
            const y = e.clientY - rect.top;  // y position inside element
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = ((centerY - y) / centerY) * 8; // Max 8 degrees tilt
            const rotateY = ((x - centerX) / centerX) * 8; // Max 8 degrees tilt
            
            card.style.transform = `translateY(-10px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
            card.style.transition = 'none';
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0) rotateX(0) rotateY(0)';
            card.style.transition = 'transform 0.5s cubic-bezier(0.25, 1, 0.5, 1), box-shadow 0.5s cubic-bezier(0.25, 1, 0.5, 1)';
        });
    });
    */

    // --- 3. Hero Floating Aroma Mist Sparkles ---
    const hero = document.querySelector('.hero-section');
    if (hero) {
        const spawnBubble = () => {
            const bubble = document.createElement('div');
            const size = Math.random() * 40 + 20;
            bubble.style.position = 'absolute';
            bubble.style.width = `${size}px`;
            bubble.style.height = `${size}px`;
            bubble.style.borderRadius = '50%';
            bubble.style.backgroundColor = 'rgba(255, 90, 121, 0.05)';
            bubble.style.filter = 'blur(5px)';
            bubble.style.left = `${Math.random() * 90 + 5}%`;
            bubble.style.bottom = '10px';
            bubble.style.pointerEvents = 'none';
            bubble.style.zIndex = '0';
            
            const speedY = Math.random() * 1.2 + 0.4;
            const amplitude = Math.random() * 25 + 10;
            const frequency = Math.random() * 0.02 + 0.005;
            let currentY = 0;
            let currentX = 0;
            let time = 0;
            
            hero.appendChild(bubble);
            
            const animateBubble = () => {
                currentY += speedY;
                time += frequency;
                currentX = Math.sin(time) * amplitude;
                
                bubble.style.transform = `translate(${currentX}px, -${currentY}px)`;
                bubble.style.opacity = (1 - (currentY / 350)).toString();
                
                if (currentY < 350) {
                    requestAnimationFrame(animateBubble);
                } else {
                    bubble.remove();
                }
            };
            animateBubble();
        };

        // Spawn a bubble every 900ms
        setInterval(spawnBubble, 900);
    }

    // --- 4. Sliding Navbar Menu Underline Animation ---
    const navbarMenu = document.getElementById('js-navbar-menu');
    if (navbarMenu) {
        const underline = document.createElement('div');
        underline.className = 'nav-underline';
        navbarMenu.appendChild(underline);

        const currentPath = window.location.pathname;
        const navLinks = navbarMenu.querySelectorAll('.nav-link');
        let activeLink = null;
        let activeIndex = -1;

        navLinks.forEach((link, idx) => {
            const href = link.getAttribute('href');
            if (href) {
                const isHome = currentPath.endsWith('index.php') || currentPath === '/aromatherapy_store/' || currentPath === '/aromatherapy_store';
                if (isHome) {
                    if (href.endsWith('index.php')) {
                        activeLink = link;
                        activeIndex = idx;
                    }
                } else {
                    const pageName = href.split('/').pop();
                    if (pageName && currentPath.includes(pageName)) {
                        activeLink = link;
                        activeIndex = idx;
                    }
                }
            }
        });

        const getPlacement = (el) => {
            const rect = el.getBoundingClientRect();
            const menuRect = navbarMenu.getBoundingClientRect();
            return {
                left: rect.left - menuRect.left,
                width: rect.width
            };
        };

        const updateUnderlinePosition = () => {
            if (activeLink) {
                const pos = getPlacement(activeLink);
                underline.style.left = `${pos.left}px`;
                underline.style.width = `${pos.width}px`;
            }
        };

        if (activeLink) {
            const activePos = getPlacement(activeLink);
            const prevIndexVal = sessionStorage.getItem('prevActiveLinkIndex');
            
            if (prevIndexVal !== null) {
                const prevIndex = parseInt(prevIndexVal);
                sessionStorage.removeItem('prevActiveLinkIndex'); // Clean up state

                if (prevIndex !== activeIndex && prevIndex >= 0 && prevIndex < navLinks.length) {
                    const prevLink = navLinks[prevIndex];
                    if (prevLink) {
                        const prevPos = getPlacement(prevLink);
                        
                        // Set start position at previous link coordinate (instantly)
                        underline.style.transition = 'none';
                        underline.style.left = `${prevPos.left}px`;
                        underline.style.width = `${prevPos.width}px`;

                        // Slide to new coordinate on next repaint cycle
                        requestAnimationFrame(() => {
                            setTimeout(() => {
                                underline.style.transition = 'left 0.4s cubic-bezier(0.25, 1, 0.5, 1), width 0.4s cubic-bezier(0.25, 1, 0.5, 1)';
                                underline.style.left = `${activePos.left}px`;
                                underline.style.width = `${activePos.width}px`;
                            }, 50);
                        });
                    }
                } else {
                    updateUnderlinePosition();
                }
            } else {
                updateUnderlinePosition();
            }
        }

        navLinks.forEach((link, idx) => {
            link.addEventListener('click', () => {
                if (activeIndex !== -1) {
                    sessionStorage.setItem('prevActiveLinkIndex', activeIndex.toString());
                }
            });

            link.addEventListener('mouseenter', () => {
                const pos = getPlacement(link);
                underline.style.left = `${pos.left}px`;
                underline.style.width = `${pos.width}px`;
            });
        });

        navbarMenu.addEventListener('mouseleave', () => {
            if (activeLink) {
                updateUnderlinePosition();
            } else {
                underline.style.width = '0px';
            }
        });

        window.addEventListener('resize', updateUnderlinePosition);
    }
});
