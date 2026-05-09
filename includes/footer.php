        </div> <!-- End container-fluid -->
    </main>

    <footer class="text-center py-4 text-muted small mt-auto">
        <div class="container">
            <hr>
            <p class="mb-0">QuickMark - Presented by Satish & team...</p>
            <p class="mb-0">&copy; <?php echo date('Y'); ?> All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle for Mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const mainSidebar = document.getElementById('mainSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                mainSidebar.classList.toggle('show');
                sidebarOverlay.classList.toggle('show');
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', () => {
                mainSidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            });
        }

        // Active link enhancement
        document.querySelectorAll('.sidebar .nav-link').forEach(link => {
            if (link.href === window.location.href) {
                link.classList.add('active');
            }
        });

        // Copy to Clipboard Utility
        function copyToClipboard(event) {
            const binaryOutput = document.getElementById('binaryOutput');
            if (!binaryOutput || !binaryOutput.textContent.trim()) {
                alert('Nothing to copy!');
                return;
            }
            const text = binaryOutput.textContent;
            navigator.clipboard.writeText(text).then(function() {
                const button = event.target.closest('button') || event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Copied!';
                button.classList.replace('btn-primary', 'btn-success');
                button.classList.replace('btn-outline-primary', 'btn-success');
                
                setTimeout(function() {
                    button.innerHTML = originalText;
                    button.classList.replace('btn-success', 'btn-primary');
                    button.classList.replace('btn-success', 'btn-outline-primary');
                }, 2000);
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
            });
        }
    </script>
</body>
</html>
