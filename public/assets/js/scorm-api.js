/* LMSAdvisor SCORM 1.2 / 2004 API Runtime — Phase 1 stub
   Full implementation in Phase 13 (Student PWA / Lesson Player) */
window.API = window.API || (function () {
  var data = {};
  return {
    LMSInitialize:    function () { return 'true'; },
    LMSFinish:        function () { return 'true'; },
    LMSGetValue:      function (k) { return data[k] || ''; },
    LMSSetValue:      function (k, v) { data[k] = v; return 'true'; },
    LMSCommit:        function () { return 'true'; },
    LMSGetLastError:  function () { return '0'; },
    LMSGetErrorString:function () { return ''; },
    LMSGetDiagnostic: function () { return ''; },
  };
}());

// SCORM 2004
window.API_1484_11 = window.API_1484_11 || (function () {
  var data = {};
  return {
    Initialize:     function () { return 'true'; },
    Terminate:      function () { return 'true'; },
    GetValue:       function (k) { return data[k] || ''; },
    SetValue:       function (k, v) { data[k] = v; return 'true'; },
    Commit:         function () { return 'true'; },
    GetLastError:   function () { return '0'; },
    GetErrorString: function () { return ''; },
    GetDiagnostic:  function () { return ''; },
  };
}());
