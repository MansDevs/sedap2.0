        </main>
    </div>
</div>

<script>
(function () {
    const sidebar = document.getElementById('doctorSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const openBtn = document.getElementById('sidebarOpenBtn');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const railMenuToggleBtn = document.getElementById('railMenuToggleBtn');
    const railCollapseBtn = document.getElementById('railCollapseBtn');

    function openSidebar() {
        if (sidebar) sidebar.classList.remove('-translate-x-full');
        if (overlay) overlay.classList.remove('hidden');
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.add('-translate-x-full');
        if (overlay) overlay.classList.add('hidden');
    }

    function toggleDesktopRail() {
        if (!sidebar) return;
        const willCollapse = !sidebar.classList.contains('collapsed');
        sidebar.classList.toggle('collapsed', willCollapse);
        document.documentElement.classList.toggle('sidebar-collapsed', willCollapse);
        try {
            localStorage.setItem('doctor_sidebar_collapsed', willCollapse ? 'true' : 'false');
        } catch(e) {}
    }

    if (openBtn) openBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // Menu toggle inside the nav rail
    if (railMenuToggleBtn) {
        railMenuToggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (window.innerWidth >= 768) {
                toggleDesktopRail();
            }
        });
    }
    if (railCollapseBtn) {
        railCollapseBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDesktopRail();
        });
    }
})();
</script>

</body>
</html>
