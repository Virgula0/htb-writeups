var jddiv = {};
jddiv.Pager = function() {
    this.paragraphsPerPage = 3;
    this.currentPage = 1;
	this.labelPage = '';
	this.labelOf = '';
	this.labelStart = '';
	this.labelEnd = '';
	this.labelPrev = '';
	this.labelNext = '';
	
    this.pagingControlsContainer = '#jd_page_nav';
    this.pagingContainerPath = '#results';

    this.numPages = function() {
        var numPages = 0;
        if (this.paragraphs != null && this.paragraphsPerPage != null) {
            numPages = Math.ceil(this.paragraphs.length / this.paragraphsPerPage);
        }
        
        return numPages;
    };

    this.showPage = function(page) {
        this.currentPage = page;
        var html = '';

        this.paragraphs.slice((page-1) * this.paragraphsPerPage,
            ((page-1)*this.paragraphsPerPage) + this.paragraphsPerPage).each(function() {
            html += '<div>' + jQuery(this).html() + '</div>';
        });

        jQuery(this.pagingContainerPath).html(html);

        renderControls(this.pagingControlsContainer, this.currentPage, this.numPages(), this.labelPage, this.labelOf, this.labelStart, this.labelEnd, this.labelPrev, this.labelNext);
    }

    var renderControls = function(container, currentPage, numPages, labelPage, labelOf, labelStart, labelEnd, labelPrev, labelNext) {
        var pagingControls = '<ul class="jd_pagination_list">';
            
		switch (currentPage){
			
			case 1:
				pagingControls += '<li class="pagination-start"><span class="pagenav">' + labelStart + '</span></li>';
				pagingControls += '<li class="pagination-prev"><span class="pagenav">' + labelPrev + '</span></li>';
				pagingControls += '<li><span class="pagenav">' + 1 + '</span></li>';
				for (var x = 2; x <= numPages; x++) {
					pagingControls += '<li class=""><a class="pagenav" href="#" onclick="pager.showPage(' + x + '); return false;">' + x + '</a></li>';
				}
				if (currentPage < numPages){
					pagingControls += '<li class="pagination-next"><a class="pagenav" href="#" onclick="pager.showPage(' + (currentPage+1) + '); return false;">' + labelNext + '</a></li>';
            } else {
					pagingControls += '<li class="pagination-next"><a class="pagenav" href="#" onclick="pager.showPage(' + numPages + '); return false;">' + labelNext + '</a></li>';
				}
				pagingControls += '<li class="pagination-end"><a class="pagenav" href="#" onclick="pager.showPage(' + numPages + '); return false;">' + labelEnd + '</a></li>';
				break;
				
			case numPages:
				pagingControls += '<li class="pagination-start"><a class="pagenav" href="#" onclick="pager.showPage(' + 1 + '); return false;">' + labelStart + '</a></li>';
				pagingControls += '<li class="pagination-prev"><a class="pagenav" href="#" onclick="pager.showPage(' + (numPages-1) + '); return false;">' + labelPrev + '</a></li>';
				for (var x = 1; x <= (numPages-1); x++) {
					pagingControls += '<li class=""><a class="pagenav" href="#" onclick="pager.showPage(' + x + '); return false;">' + x + '</a></li>';
				}
				pagingControls += '<li><span class="pagenav">' + numPages + '</span></li>';
				pagingControls += '<li class="pagination-next"><span class="pagenav">' + labelNext + '</span></li>';
				pagingControls += '<li class="pagination-end"><span class="pagenav">' + labelEnd + '</span></li>';
				break;

			default:
				pagingControls += '<li class="pagination-start"><a class="pagenav" href="#" onclick="pager.showPage(' + 1 + '); return false;">' + labelStart + '</a></li>';
				pagingControls += '<li class="pagination-prev"><a class="pagenav" href="#" onclick="pager.showPage(' + (numPages-1) + '); return false;">' + labelPrev + '</a></li>';
				for (var x = 1; x <= (numPages); x++) {
					if (x != currentPage){
						pagingControls += '<li class=""><a class="pagenav" href="#" onclick="pager.showPage(' + x + '); return false;">' + x + '</a></li>';
					} else {
						pagingControls += '<li><span class="pagenav">' + x + '</span></li>';
					}
            }
				pagingControls += '<li class="pagination-next"><a class="pagenav" href="#" onclick="pager.showPage(' + (currentPage+1) + '); return false;">' + labelNext + '</a></li>';
				pagingControls += '<li class="pagination-end"><a class="pagenav" href="#" onclick="pager.showPage(' + numPages + '); return false;">' + labelEnd + '</a></li>';
        }

        pagingControls += '</ul>';

        jQuery(container).html(pagingControls);
    }
}