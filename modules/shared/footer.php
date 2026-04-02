<?php
/**
 * Shared Footer Component for MySan Modules
 * Closes the layout opened by header.php
 * 
 * Usage: include this file at the end of each module page, before closing body/html tags
 */
?>
        </main>
    </div>

    <!-- Shared Scripts -->
    <script src="../../assets/js/shared.js"></script>
    
    <!-- Page-specific scripts -->
    <?php echo isset($extraScripts) ? $extraScripts : ''; ?>
</body>
</html>
