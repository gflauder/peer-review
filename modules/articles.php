<?php

/**
 * Articles
 *
 * PHP version 5
 *
 * @category  Application
 * @package   PyriteView
 * @author    Stéphane Lavergne <lis@imars.com>
 * @copyright 2017 Stéphane Lavergne
 * @license   http://www.gnu.org/licenses/agpl-3.0.txt  GNU Affero GPL version 3
 * @link      https://github.com/vphantom/pyriteview
 */

/**
 * Articles class
 *
 * PHP version 5
 *
 * @category  Application
 * @package   PyriteView
 * @author    Stéphane Lavergne <lis@imars.com>
 * @copyright 2017 Stéphane Lavergne
 * @license   http://www.gnu.org/licenses/agpl-3.0.txt  GNU Affero GPL version 3
 * @link      https://github.com/vphantom/pyriteview
 */

class Articles
{
    /**
     * Bootstrap: define event handlers
     *
     * @return null
     */
    public static function bootstrap()
    {
        on('install',      'Articles::install');
        on('articles',     'Articles::getList');
        on('article',      'Articles::get');
        on('article_save', 'Articles::save');
    }

    /**
     * Create database tables if necessary
     *
     * @return null
     */
    public static function install()
    {
        global $PPHP;
        $db = $PPHP['db'];
        $config = $PPHP['config']['articles'];

        echo "    Installing articles...";
        $db->begin();
        $db->exec(
            "
            CREATE TABLE IF NOT EXISTS 'articleStatus' (
                name VARCHAR(64) NOT NULL DEFAULT '' PRIMARY KEY
            )
            "
        );
        foreach ($config['states'] as $status) {
            $db->exec("REPLACE INTO articleStatus (name) VALUES (?)", array($status));
        };
        $db->exec(
            "
            CREATE TABLE IF NOT EXISTS 'articles' (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                issueId     INTEGER NOT NULL DEFAULT '0',
                status      VARCHAR(64) NOT NULL DEFAULT 'created',
                wordCount   INTEGER NOT NULL DEFAULT '0',
                title       VARCHAR(255),
                keywords    TEXT NOT NULL DEFAULT '',
                abstract    TEXT NOT NULL DEFAULT '',
                FOREIGN KEY(status) REFERENCES articleStatus(name)
            )
            "
        );
        $db->commit();
        echo "    done!\n";
    }

    /**
     * Get an article
     *
     * Only works if the current user is allowed to view it.
     *
     * Special columns volume, number and issue are added to describe the
     * issue in which the article currently belongs.  The issue is either
     * "{$volume}.{$number}", $number or "0" depending on their values.
     *
     * @param int $id Which article to load
     *
     * @return array|bool Article (associative) or false on failure
     */
    public static function get($id)
    {
        global $PPHP;
        $db = $PPHP['db'];
        $config = $PPHP['config']['articles'];
        $article = false;

        $article = $db->selectSingleArray(
            "
            SELECT articles.*, issues.volume, issues.number FROM articles
            LEFT JOIN issues ON issues.id=articles.issueId
            WHERE articles.id=?
            ",
            array($id)
        );
        if (pass('can', 'view', 'article', $id)
            || pass('can', 'view', 'issue', $article['issueId'])
            || pass('can', 'review', 'article', $id)
            || pass('can', 'edit', 'article', $id)
            || pass('can', 'edit', 'issue', $article['issueId'])
        ) {

            if ($article !== false) {
                $article['keywords'] = dejoin(';', $article['keywords']);
                $article['permalink'] = makePermalink($article['title']);
                $article['authors'] = grab('object_users', 'edit', 'article', $id);
                $article['peers'] = grab('object_users', 'review', 'article', $id);
                $article['editors'] = grab('object_users', '*', 'issue', $article['issueId']);
                if (count($article['editors']) < 1) {
                    $article['editors'] = grab('role_users', 'editor-in-chief');
                };

                /*
                 * Try to open config.articles.path '/' article.issue '/' article.id as directory
                 */
                $article['files'] = array();
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($article['number'] !== '') {
                    $article['issue'] = ($article['volume'] !== '' ? $article['volume'] . '.' : '') . $article['number'];
                } else {
                    $article['issue'] = '0';
                };
                foreach (glob("{$config['path']}/{$article['issue']}/{$article['id']}/*.*") as $fname) {
                    $bytes = filesize($fname);
                    $pi = pathinfo($fname);
                    $article['files'][] = array(
                        'dir' => $pi['dirname'],
                        'name' => $pi['basename'],
                        'bytes' => $bytes,
                        'type' => finfo_file($finfo, $fname)
                    );
                };
                finfo_close($finfo);
            };
        } else {
            return array();
        };

        return $article;
    }

    /**
     * Get articles, most recent first
     *
     * Only articles which the current user is allowed to view are returned.
     *
     * Convenience virtual columns 'volume', 'number' and 'issue' are fetched
     * from the issues table as well in addition to 'issueId'.
     *
     * The following keys may be defined in $args:
     *
     * keyword: Search in titles, keywords, abstracts
     * issueId: Restrict to a specific issue
     * states: Restrict to specific states (string for one, array for many)
     * byStatus: Set true to group results by status code
     * current: Status in states_wip[] OR status in states_final[] in recent/future issues
     *
     * @param array $args (Optional) Arguments described above
     *
     * @return array Articles or arrays keyed by status
     */
    public static function getList($args = array())
    {
        global $PPHP;
        $db = $PPHP['db'];
        $config = $PPHP['config']['articles'];
        $res = array();
        $keyword = null;
        $issueId = null;
        $current = false;
        $byStatus = false;
        $states = array();

        foreach ($args as $key => $val) {
            switch ($key) {
            case 'keyword':
                $keyword = $val;
                break;
            case 'issueId':
                $issueId = $val;
                break;
            case 'states':
                if (is_array($val)) {
                    $states = $val;
                } else {
                    $states[] = $val;
                };
                break;
            case 'current':
                $current = true;
            case 'byStatus':
                $byStatus = $val;
                break;
            };
        };

        $q = $db->query('SELECT articles.*, issues.volume, issues.number FROM articles');
        $q->left_join('issues ON issues.id=articles.issueId');
        $q->where();
        $sources = array();
        $sources[] = grab('can_sql', 'issues.id', 'view', 'issue');
        $sources[] = grab('can_sql', 'articles.id', 'view', 'article');
        $sources[] = grab('can_sql', 'articles.id', 'review', 'article');
        if (pass('has_role', 'author')) {
            $sources[] = grab('can_sql', 'articles.id', 'edit', 'article');
        };
        $q->implodeClosed('OR', $sources);
        if (pass('has_role', 'reader')) {
            $q->and("articles.status='published'");
        };
        if ($issueId !== null) {
            $q->and('articles.issueId=?', $issueId);
        };
        if (count($states) > 0) {
            $q->append('AND articles.status IN')->varsClosed($states);
        };
        if ($current) {
            $search = array();

            $search[] = $db->query('articles.status IN')
                ->varsClosed($config['states_wip']);

            $search[] = $db->query('articles.status IN')
                ->varsClosed($config['states_final'])
                ->and("issues.publication > date('now', '-1 month')");

            $q->and()->implodeClosed('OR', $search);
        };
        if ($keyword !== null) {
            $search = array();
            $search[] = $db->query('articles.title LIKE ?', "%{$keyword}%");
            $search[] = $db->query('articles.keywords LIKE ?', "%{$keyword}%");
            $search[] = $db->query('articles.abstract LIKE ?', "%{$keyword}%");
            $q->and()->implodeClosed('OR', $search);
        };
        $q->order_by('issues.volume DESC, issues.number DESC, articles.id DESC');

        if ($PPHP['config']['global']['debug']) {
            print_r($q);
        };
        $list = $db->selectArray($q);
        foreach ($list as $key => $article) {
            // Weird bug with PHP using $list => &$article
            $list[$key]['keywords'] = dejoin(';', $article['keywords']);
            $list[$key]['permalink'] = makePermalink($article['title']);
            if ($article['number'] !== '') {
                $list[$key]['issue'] = ($article['volume'] !== '' ? $article['volume'] . '.' : '') . $article['number'];
            } else {
                $list[$key]['issue'] = '0';
            };
        };

        if ($byStatus) {
            $sorted = array();
            foreach ($list as $article) {
                $sorted[$article['status']][] = $article;
            };
            return $sorted;
        } else {
            return $list;
        };
    }

    /**
     * Save/create an article
     *
     * Additional key 'id' specifies that we're trying to update an existing
     * article.
     *
     * @param array $cols Article information
     *
     * @return int|bool The Id (possibly created) on success, false on error
     */
    public static function save($cols)
    {
        global $PPHP;
        $db = $PPHP['db'];
        $res = false;

        $db->begin();
        if (isset($cols['keywords']) && is_array($cols['keywords'])) {
            $cols['keywords'] = implode(';', $cols['keywords']);
        };
        if (isset($cols['id'])) {
            $id = $cols['id'];
            unset($cols['id']);
            $oldArticle = self::get($id);
            $res = $db->update('articles', $cols, 'WHERE id=?', array($id));
            if ($res !== false) {

                if ($oldArticle['issueId'] !== $cols['issueId'] && count($oldArticle['files']) > 0) {
                    // Move files directory if issue was reassigned
                    $config = $PPHP['config']['articles'];
                    $issue = grab('issue', $cols['issueId']);
                    $issuePath = $config['path'] . '/' . $issue['issue'];
                    if (!file_exists($issuePath)) {
                        mkdir($issuePath, 06770);
                    };
                    rename(
                        "{$config['path']}/{$oldArticle['issue']}/{$id}",
                        "{$config['path']}/{$issue['issue']}/{$id}"
                    );
                };

                $log = array(
                    'action' => 'modified',
                    'objectType' => 'article',
                    'objectId' => $id
                );
                if (isset($cols['status']) && $oldArticle['status'] !== $cols['status']) {
                    $log['fieldName'] = 'status';
                    $log['oldValue'] = $oldArticle['status'];
                    $log['newValue'] = $cols['status'];
                };
                if (isset($cols['log'])) {
                    $log['content'] = $cols['log'];
                };
                trigger('log', $log);
            };
        } else {
            $res = $db->insert('articles', $cols);
            if ($res !== false) {
                trigger('http_status', 201);
                trigger(
                    'log',
                    array(
                        'action' => 'created',
                        'objectType' => 'article',
                        'objectId' => $res
                    )
                );
            };
        };
        if ($res !== false) {
            if (isset($cols['authors'])) {
                $oldAuthors = grab('object_users', 'edit', 'article', $res);
                $deled = array_diff($oldAuthors, $cols['authors']);
                $added = array_diff($cols['authors'], $oldAuthors);
                foreach ($added as $author) {
                    trigger('grant', $author, 'author');
                    trigger('grant', $author, null, 'edit', 'article', $res);
                };
                foreach ($deled as $author) {
                    trigger('revoke', $author, null, 'edit', 'article', $res);
                };
            };

            if (isset($cols['peers'])) {
                $oldPeers = grab('object_users', 'review', 'article', $res);
                $deled = array_diff($oldPeers, $cols['peers']);
                $added = array_diff($cols['peers'], $oldPeers);
                foreach ($added as $peer) {
                    trigger('grant', $peer, 'peer');
                    trigger('grant', $peer, null, 'review', 'article', $res);
                };
                foreach ($deled as $author) {
                    trigger('revoke', $author, null, 'review', 'article', $res);
                };
            };
        };
        $db->commit();

        return $res;
    }
}

// Routes

on(
    'route/articles',
    function ($path) {
        global $PPHP;
        $config = $PPHP['config']['articles'];

        if (!$_SESSION['identified']) return trigger('http_status', 403);
        $req = grab('request');
        $articleId = array_shift($path);
        $article = grab('article', $articleId);

        // A binary request is necessarily for a file within an article
        //
        if ($req['binary']) {
            if ($article === false) return trigger('http_status', 404);
            if (!(pass('can', 'view', 'article', $articleId)
                || pass('can', 'review', 'article', $articleId)
                || pass('can', 'edit', 'article', $articleId)
                || pass('can', 'view', 'issue', $article['issueId']))
            ) return trigger('http_status', 403);

            $fname = array_shift($path);

            foreach ($article['files'] as $file) {
                if ($file['name'] === $fname) {
                    header('Content-Type: ' . $file['type']);
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . $file['bytes']);
                    readfile($file['dir'] . '/' . $file['name']);
                    exit;
                };
            };
            return trigger('http_status', 404);
        };

        // Non-binary request can be for general listing or a specific article
        //
        if ($articleId !== null) {
            $created = false;
            $uploaded = false;
            $bad_format = true;
            $saved = false;
            $success = false;
            $file_success = false;
            $history = null;
            if (isset($req['post']['wordCount'])) {
                if (!pass('form_validate', 'articles_edit')) return trigger('http_status', 440);
                if (is_numeric($articleId)) {
                    if (!(pass('can', 'edit', 'article', $articleId) || pass('can', 'edit', 'issue', $article['issueId']))) return trigger('http_status', 403);
                } else {
                    if (!pass('can', 'create', 'article')) return trigger('http_status', 403);
                };
                if (!isset($req['post']['userdata'])) {
                    $req['post']['userdata'] = array();
                };
                $req['post']['authors'] = grab('clean_userids', $req['post']['authors'], $req['post']['userdata']);
                if (isset($req['post']['peers'])) {
                    $req['post']['peers']   = grab('clean_userids', $req['post']['peers'], $req['post']['userdata']);
                };
                $saved = true;
                $success = grab('article_save', $req['post']);

                if ($success !== false && !is_numeric($articleId)) {
                    $articleId = $success;
                    $created = true;
                }
                // Refresh to discover changes/creation before file handling
                $article = grab('article', $articleId);

                // Only send if updated, not created, and we're an author
                if ($success
                    && is_numeric($articleId)
                    && in_array($_SESSION['user']['id'], $article['authors'])
                ) {
                    trigger(
                        'sendmail',
                        $article['authors'],
                        $article['editors'],
                        $article['peers'],
                        'editarticle',
                        array(
                            'article' => $article,
                            'log' => $req['post']['log']
                        ),
                        true  // Author-editors could see Bcc
                    );
                };

                // Handle file uploads
                foreach ($req['files'] as $file) {
                    $uploaded = true;
                    if (in_array($file['type'], $config['file_types'])
                        && in_array($file['extension'], $config['file_extensions'])
                    ) {
                        $bad_format = false;
                        $base = filter('clean_filename', $file['filename']);
                        $ext = filter('clean_filename', $file['extension']);
                        $base = "{$config['path']}/{$article['issue']}/{$article['id']}/{$base}";

                        if (!file_exists("{$config['path']}/{$article['issue']}")) {
                            mkdir("{$config['path']}/{$article['issue']}", 06770);
                        };
                        if (!file_exists("{$config['path']}/{$article['issue']}/{$article['id']}")) {
                            mkdir("{$config['path']}/{$article['issue']}/{$article['id']}", 06770);
                        };

                        // Attempt to save the file, avoiding name collisions
                        $i = 2;
                        $try = "{$base}.{$ext}";
                        while (file_exists($try) && $i < 100) {
                            $try = "{$base}_{$i}.{$ext}";
                            $i++;
                        };
                        if (move_uploaded_file($file['tmp_name'], $try)) {
                            $file_success = true;
                            $pi = pathinfo($try);
                            trigger(
                                'log',
                                array(
                                    'action' => 'attached',
                                    'objectType' => 'article',
                                    'objectId' => $articleId,
                                    'fieldName' => 'files',
                                    'newValue' => $pi['basename']
                                )
                            );
                        };
                    };
                };

                if ($created) return trigger('http_redirect', $req['base'] . '/articles/' . $articleId . '/' . $article['permalink']);

                // Refresh to discover new files
                if ($uploaded) {
                    $article = grab('article', $articleId);
                };
            };
            if (isset($req['get']['unlink'])) {
                if (!pass('can', 'delete', 'article', $articleId)) return trigger('http_status', 403);
                $saved = true;
                $fname = array_shift($path);
                foreach ($article['files'] as $file) {
                    if ($file['name'] === $fname) {
                        $success = unlink($file['dir'] . '/' . $file['name']);
                        break;
                    };
                };

                if ($success) return trigger('http_redirect', $req['base'] . '/articles/' . $articleId . '/' . $article['permalink']);

                // Reload to be aware of changes
                $article = grab('article', $articleId);
            };
            if (is_numeric($articleId)) {


                // View only from this point
                if (!(pass('can', 'view', 'article', $articleId)
                    || pass('can', 'review', 'article', $articleId)
                    || pass('can', 'edit', 'article', $articleId)
                    || pass('can', 'view', 'issue', $article['issueId']))
                ) return trigger('http_status', 403);

                $history = grab(
                    'history',
                    array(
                        'objectType' => 'article',
                        'objectId' => $articleId,
                        'order' => 'DESC'
                    )
                );
            } else {
                // New article editor
                $article['authors'] = array($_SESSION['user']['id']);
            };
            trigger(
                'render',
                'articles_edit.html',
                array(
                    'saved' => $saved,
                    'success' => $success,
                    'file_success' => $file_success,
                    'uploaded' => $uploaded,
                    'bad_format' => $bad_format,
                    'article' => $article,
                    'issues' => grab('issues'),
                    'history' => $history
                )
            );
        } else {
            $search = array('byStatus' => true);
            if (isset($req['post']['keyword'])) {
                if (!pass('form_validate', 'article_search')) return trigger('http_status', 440);
                $search['keyword'] = $req['post']['keyword'];
            } else {
                $search['current'] = true;
            };
            trigger(
                'render',
                'articles.html',
                array(
                    'articles' => grab('articles', $search)
                )
            );
        };

    }
);
