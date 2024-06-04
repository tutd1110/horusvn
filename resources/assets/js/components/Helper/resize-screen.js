export const resizeScreen = () => {
    try {
        var height = 0

        var headerChome = window.screen.height > window.screen.width ? 160 : 90 ;
        var taskbarSystem = 120 ;
        
        var headerPc = document.getElementsByClassName('header-pc')[0].clientHeight;
        
        var headerMb = document.getElementsByClassName('header-mobile')[0].clientHeight;
        
        var breadcrumb = document.getElementsByClassName('el-page-header__header')[0].clientHeight + 16 + 16;

        var headerTab = document.getElementsByClassName('custom-height-tabs')[0] ? document.getElementsByClassName('custom-height-tabs')[0].querySelector('.el-tabs__header').clientHeight + 15 : 0;
        
        var paddingBody = 20 + 20;
        
        var filterHeight = document.getElementById('filter-block').clientHeight + 20;

        var headerTable = document.getElementsByClassName('ant-table-thead')[0] ? document.getElementsByClassName('ant-table-thead')[0].clientHeight : 0
        
        var paginateElement = document.getElementsByClassName('ant-pagination')[0] || document.getElementsByClassName('el-pagination')[0];
        var paginate = paginateElement ? (document.getElementsByClassName('el-pagination')[0] ? paginateElement.clientHeight + 50 : paginateElement.clientHeight + 20) : 0;

        // height = window.screen.height - (filterHeight + headerPc + headerMb + paginate + paddingBody + breadcrumb + headerChome + taskbarSystem + headerTable);
        height = window.innerHeight - (filterHeight + headerPc + headerMb + paginate + paddingBody + breadcrumb  + headerTable + headerTab );
        return height;
    } catch(err) {
        console.log(err.message)
    }
}
