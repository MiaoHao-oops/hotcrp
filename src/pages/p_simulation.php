<?php
class SimulationExtension {
    static function banner(Contact $user, Qrequest $qreq) {
        $id = defined('SIM_CONF_ID') ? SIM_CONF_ID : getenv('SIM_CONF_ID');
        $url = '/?conference=' . rawurlencode($id);
        echo '<div class="msg msg-info" style="margin:16px 0"><strong>本地模拟审稿 · Codex agents</strong> — ';
        echo '论文提交后自动开始模拟审稿。意见只适用于其标记的论文版本，不代表真实会议决定。 ';
        echo '<a href="' . htmlspecialchars($url) . '">会议规则、任务进度与重试 →</a></div>';
    }
}
