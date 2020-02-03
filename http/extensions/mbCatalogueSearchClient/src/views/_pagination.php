<div class="pager">
    <ul class="pager--list" data-id="<?php echo $params['name'] ?>">
        <?php

        $activePage     = $params['activePage'];
        $startPage      = $params['startPage'];
        $endPage        = $params['endPage'];
        $resultsCount   = $params['resultsCount'];
        $resultsPerPage = $params['rpp'];
        $pages          = $params['pages'];
        $i              = $startPage;

        $minPage        = 1; //minPage to show
        $maxPageCount   = 6;
        $pagesPerSide   = 3;

        $pagesToRender = array(1);
        for ($page = $activePage - 3; $page < $activePage + 4; ++$page) {
            if ($page > 1 && $page < $pages) {
                $pagesToRender[] = $page;
            }
        }
        $pagesToRender[] = $pages;

        if ($pages > $minPage) {
            echo "<span>Seiten: </span>";
        } else {
            echo "</ul></div>";
            return;
        }
        if ($activePage > 1) {
            echo "<li class='pager--list--item -js-pager-item' data-page='" . ($activePage - 1) . "'>Zurück</li>";
        }
        $last = 0;
        foreach ($pagesToRender as $l) {
            if ($last + 1 != $l) {
                echo "<li class='pager--list--points'>...</li>";
            }
            $last = $l;
            echo "<li data-page='" . $l . "' " . ($l == $activePage ?
                                                  "class='pager--list--item -js-pager-item active-Page'" :
                                                  "class='pager--list--item -js-pager-item'") . ">" . $l . "</li>";
        }
        if ($activePage < $pages) {
            echo "<li class='pager--list--item -js-pager-item' data-page='" . ($activePage + 1) . "'>Weiter</li>";
        }

        ?>
    </ul><!-- end .pager--list -->
</div><!-- end .pager -->
