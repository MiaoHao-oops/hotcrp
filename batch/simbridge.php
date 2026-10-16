<?php
// Local CLI adapter: validates through HotCRP domain classes, never writes review SQL.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__, 2);
require $root . '/upstream/src/init.php';
try {
    $conf = initialize_conf($root . '/extensions/options.php');
    $user = $conf->root_user();
    $input = json_decode(stream_get_contents(STDIN), false, 512, JSON_THROW_ON_ERROR);
    $op = $argv[1] ?? '';
    $out = null;
    if ($op === 'setup') {
        $us = new UserStatus($user);
        $us->set_notify(false);
        if (!$us->save_user((object) ['email'=>'chair@simulation.local', 'firstName'=>'Local', 'lastName'=>'Chair', 'roles'=>'chair'])) {
            throw new RuntimeException($us->full_feedback_text());
        }
        $sv = new SettingValues($user);
        $sv->add_json_string(json_encode($input));
        $sv->parse();
        if ($sv->has_error() || !$sv->execute()) {
            throw new RuntimeException($sv->full_feedback_text());
        }
        $out = ['ok'=>true, 'settings'=>$sv->all_jsonv()];
    } else if ($op === 'papers' || $op === 'paper') {
        $pe = new PaperExport($user);
        $pe->set_include_docids(true);
        $pe->set_ignore_soft_word_limits(true);
        $out = [];
        $ps = $op === 'paper' ? [$conf->checked_paper_by_id((int)$input->pid, $user)] : $conf->paper_set([], $user);
        foreach ($ps as $p) {
            $j = $pe->paper_json($p);
            $j->sim_reviews = [];
            foreach ($p->all_full_reviews() as $r) {
                $j->sim_reviews[] = $pe->review_json($p, $r);
            }
            if ($op === 'paper') {
                $doc = $p->document(DTYPE_SUBMISSION);
                $j->sim_pdf_path = $doc ? $doc->content_file() : null;
                $j->sim_review_form = $conf->review_form()->export_storage_json();
            }
            $out[] = $j;
        }
    } else if ($op === 'review') {
        $p = $conf->checked_paper_by_id((int)$input->pid, $user);
        $rv = (new ReviewValues($user))->set_create_users(true);
        if (!$rv->parse_json($input) || !$rv->check_and_save($p) || $rv->has_error()) {
            throw new RuntimeException($rv->full_feedback_text());
        }
        $out = ['ok'=>true];
    } else if ($op === 'submit') {
        // Only used by automated integration tests; browser submissions use HotCRP.
        $ps = (new PaperStatus($user))->set_any_content_file(true);
        $ps->notify = false;
        if (!$ps->save_paper_json($input) || $ps->has_error()) {
            throw new RuntimeException($ps->full_feedback_text());
        }
        $out = ['ok'=>true, 'pid'=>$ps->paperId];
    } else {
        throw new RuntimeException('Unknown bridge command');
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
