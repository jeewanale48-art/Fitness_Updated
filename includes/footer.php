<?php if ($isUser ?? false): ?>
</main>
<footer class="ft-footer">&copy; <?= date('Y') ?> FitTrack</footer>
</div>
</div>
<?php elseif ($isAdmin ?? false): ?>
</main>
<footer class="footer">
    <p>&copy; <?= date('Y') ?> FitTrack</p>
</footer>
<?php else: ?>
</main>
<footer class="footer">
    <p>&copy; <?= date('Y') ?> FitTrack. Track your progress, one day at a time.</p>
</footer>
<?php endif; ?>
<script src="<?= $assetBase ?? '' ?>js/app.js"></script>
</body>
</html>
