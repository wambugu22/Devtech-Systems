<?php if (is_logged_in()): ?>
    </main>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-nav">
        <a href="<?php echo SITE_URL; ?>/dashboard/" class="mobile-nav-item <?php echo $current_page == 'index' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/sales/new-sale.php" class="mobile-nav-item">
            <i class="fas fa-shopping-cart"></i>
            <span>Sale</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/inventory/products.php" class="mobile-nav-item">
            <i class="fas fa-box"></i>
            <span>Products</span>
        </a>
        <a href="<?php echo SITE_URL; ?>/reports/profit-loss.php" class="mobile-nav-item">
            <i class="fas fa-chart-line"></i>
            <span>Reports</span>
        </a>
    </div> <!-- Close container -->
    
    <footer style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; text-align: center; padding: 2rem 1rem; margin-top: 3rem;">
        <p style="margin-bottom: 0.5rem; font-size: 0.95rem;">
            <i class="fas fa-seedling"></i> 
            <strong>DevTech Partners Group</strong>
        </p>
        <p style="font-size: 0.85rem; opacity: 0.9;">
            I am the owner of the system
        </p>
        <p style="font-size: 0.75rem; opacity: 0.7; margin-top: 0.5rem;">
            &copy; <?php echo date('Y'); ?> All Rights Reserved
        </p>
    </footer>
</body>
</html><?php endif; ?>

    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>