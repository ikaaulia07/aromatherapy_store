document.addEventListener("DOMContentLoaded", () => {
    // --- 1. COUNT UP ANIMATION FOR DASHBOARD COUNTERS ---
    const counters = document.querySelectorAll(".stat-info h3");
    counters.forEach(counter => {
        const target = parseInt(counter.innerText) || 0;
        if (target === 0) return;
        
        let current = 0;
        const duration = 1000; // 1 second animation duration
        const stepTime = Math.max(Math.floor(duration / target), 15);
        
        const timer = setInterval(() => {
            current += Math.ceil(target / (duration / stepTime));
            if (current >= target) {
                counter.innerText = target;
                clearInterval(timer);
            } else {
                counter.innerText = current;
            }
        }, stepTime);
    });

    // --- 2. PREMIUM CURSOR CLICK RIPPLE EFFECT ---
    document.body.addEventListener("click", (e) => {
        const ripple = document.createElement("div");
        ripple.className = "click-ripple";
        ripple.style.left = `${e.clientX}px`;
        ripple.style.top = `${e.clientY}px`;
        document.body.appendChild(ripple);
        
        // Remove after animation completes
        ripple.addEventListener("animationend", () => {
            ripple.remove();
        });
    });

    // --- 3. SIDEBAR MENUS MICRO-INTERACTIONS ---
    const menuLinks = document.querySelectorAll(".sidebar-menu li a");
    menuLinks.forEach(link => {
        link.addEventListener("mouseenter", () => {
            const icon = link.querySelector("i");
            if (icon) {
                icon.style.transform = "scale(1.2) rotate(-8deg)";
                icon.style.transition = "transform 0.3s cubic-bezier(0.25, 1, 0.5, 1)";
            }
        });
        link.addEventListener("mouseleave", () => {
            const icon = link.querySelector("i");
            if (icon) {
                icon.style.transform = "scale(1) rotate(0deg)";
            }
        });
    });
});
