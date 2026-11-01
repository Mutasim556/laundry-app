$(function () {
  "use strict";
  $("#main-wrapper").AdminSettings({
    Theme: false, // this can be true or false ( true means dark and false means light ),
    Layout: "vertical",
    LogoBg: "skin5", // Dark background for Logo header (#1d2126)
    NavbarBg: "skin5", // Dark background for Topbar Navbar (#1d2126)
    SidebarType: "full", // You can change it full / mini-sidebar / iconbar / overlay
    SidebarColor: "skin5", // Dark background for Left Sidebar (#1d2126)
    SidebarPosition: true, // it can be true / false ( true means Fixed and false means absolute )
    HeaderPosition: true, // it can be true / false ( true means Fixed and false means absolute )
    BoxedLayout: true, // it can be true / false ( true means Boxed and false means Fluid )
  });
});
