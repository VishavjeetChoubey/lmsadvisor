<footer class="admin-footer">
  <div>
    &copy; <?= date('Y') ?> <strong>LMSAdvisor</strong> &mdash; Proudly developed by LMS Advisor
  </div>
  <div class="d-flex gap-3">
    <a href="<?= \App\Core\View::url('admin/settings') ?>">Settings</a>
    <a href="<?= \App\Core\View::url('admin/reports') ?>">Reports</a>
    <a href="<?= \App\Core\View::url('admin/knowledge-base') ?>">Knowledge Base</a>
    <a href="<?= \App\Core\View::url('certificate/verify') ?>" target="_blank">Verify Certificate</a>
  </div>
</footer>
