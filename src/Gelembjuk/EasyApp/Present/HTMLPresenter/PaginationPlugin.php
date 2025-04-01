<?php 

namespace Gelembjuk\EasyApp\Present\HTMLPresenter;

class PaginationPlugin extends AbstractPlugin {
    public function pagination($params, $smarty)
    {
        if (($params['fast'] ?? '') == 'y') {
            $totalcount = $params['data']['count'];
            $start = $params['data']['offset'];
            $limit = $params['data']['limit'];
            $link = $params['data']['link'];
            $showinfo = true;
        } else {
            $totalcount = $params['total'];
            $start = $params['start'];
            $limit = $params['limit'];
            $link = $params['link'];
            $showinfo = $params['infobar'];
        }
    
        $countofpages = 0;
    
        if ($totalcount >= 1 && $limit >= 1) {
            $countofpages = ceil($totalcount/$limit);
        }
    
        $pagenum = 1;
    
        if ($limit > 0){
            $pagenum = round($start/$limit)+1;
        }
    
        $linkprefix = '&';
    
        if (strpos($link,'?') === false) {
            $linkprefix = '?';
        } elseif (substr($link,-1) == '&') {
            $linkprefix = '';
        }
    
        $link .= $linkprefix;
    
        $getOffset = function ($pagenum, $limit, $total) {
            $offset = ($pagenum - 1) * $limit;
            
            if ($offset >= $total) {
                $offset = $total;
            }
            
            return $offset;
        };
    
        $str = '<nav><ul class="pagination">';
    
        if ($limit > 0 && $countofpages >1) {
            // show count of pages
            if ($showinfo) {
                $str .= '<li class="disabled"><span aria-hidden="true">'.($getOffset($pagenum,$limit,$totalcount) + 1).'-'.
                    ($getOffset($pagenum + 1,$limit,$totalcount)).' '.$this->context->translation->getText('from').' '.$totalcount.'</span></li>';
            }
            
            // first and previous
            $linkstatus = ($pagenum == 1)?'disabled':'';
    
            $str .= '<li class="'.$linkstatus.'">'.
                '<a href="'. $link . 'start=0&limit='.$limit.'" aria-label="'.$this->context->translation->getText('First').'">'.
                '<span aria-hidden="true">&laquo;</span></a></li>';
            $str .= '<li class="'.$linkstatus.'">'.
                '<a href="' . $link . 'start=' . $getOffset($pagenum-1,$limit,$totalcount) . '&limit='.$limit . '" aria-label="'.$this->context->translation->getText('Previous').'">'.
                '<span aria-hidden="true">&lt;</span></a></li>';
                
            
            $show_number = $pagenum - 5;
            $show_number2 = $pagenum + 5 ;
    
            if ($show_number < 1) {
                $show_number = 1;
            }
            if ($show_number2 > $countofpages) {
                $show_number2 = $countofpages;
            }
    
            for ($i = $show_number; $i <= $show_number2; $i++) {
                if ($i != $pagenum) {
                    $str .= '<li><a href="' . $link . 'start=' . $getOffset($i,$limit,$totalcount).
                        '&limit=' . $limit.'">' . $i . '</a></li>';
                }else{
                    $str .= '<li class="active"><a href="#">' . $i . '<span class="sr-only">(current)</span></a></li>';
                }
            }
    
            
            $linkstatus = ($pagenum == $countofpages)?'disabled':'';
    
            $str .= '<li class="'.$linkstatus.'">'.
                '<a href="'. $link . 'start=' . $getOffset($pagenum+1,$limit,$totalcount). '&limit='.$limit.'" aria-label="'.$this->context->translation->getText('Next').'">'.
                '<span aria-hidden="true">&gt;</span></a></li>';
            $str .= '<li class="'.$linkstatus.'">'.
                '<a href="' . $link . 'start=' . $getOffset($countofpages,$limit,$totalcount) . '&limit='.$limit . '" aria-label='.$this->context->translation->getText('Last').'">'.
                '<span aria-hidden="true">&raquo;</span></a></li>';
            
        }
    
        $str .= "</ul></nav>";
        
        return $str;
    }
}
