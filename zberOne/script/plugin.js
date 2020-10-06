$(function () {
  if (location.hash !== "#zberOne"){
    return;
  }
  $("a:visible").each(function () {
    const url = $(this).attr("href");
    if (url.indexOf("://www.zblogcn.com/") > -1) {
      // console.log(url);
      zbp.cookie.set("zberOne", "pass");
    }
    location.href = `${bloghost}zb_users/plugin/zberOne/main.php`;
  });
});
