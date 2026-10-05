<!--Bootstrap Validator [ OPTIONAL ]-->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link href="<?php echo TEMPLATE_URL; ?>/plugins/bootstrap-validator/bootstrapValidator.min.css" rel="stylesheet">
<style>
    .valid {
        color: green;
    }

    .valid:before {

        position: relative;
        left: -35px;
        content: "✔";
    }

    .invalid {
        color: red;
    }

    .invalid:before {
        position: relative;
        left: -35px;
        content: "✘";
    }

    .phone12 {
        margin: 15px auto;
        padding: 4px;
        border-radius: 40px;
        width: auto;
        display: inline-block;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25), 0 3px 15px rgba(0, 0, 0, 0.35);
        -webkit-box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25), 0 3px 15px rgba(0, 0, 0, 0.35);
        -moz-box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25), 0 3px 15px rgba(0, 0, 0, 0.35);
        position: relative;
    }

    .phone12:before {
        content: '';
        position: absolute;
        left: -4px;
        top: 200px;
        width: 4px;
        height: 50px;
        background: #000;
        display: block;
        border-radius: 4px 0 0 4px;
    }

    .phone12:after {
        content: '';
        position: absolute;
        left: -4px;
        top: 260px;
        width: 4px;
        height: 50px;
        background: #000;
        display: block;
        border-radius: 4px 0 0 4px;
    }

    .phone12 .screenborder {
        width: auto;
        height: auto;
        padding: 4px;
        border-radius: 36px;
        display: block;
        border: rgba(255, 255, 255, 0.2) 0px solid;
        box-shadow: 0 0px 5px rgba(0, 0, 0, 0.55), inset 1px 0px 3px rgba(255, 255, 255, 0.55);
        -webkit-box-shadow: 0 0px 5px rgba(0, 0, 0, 0.55), inset 1px 0px 3px rgba(255, 255, 255, 0.55);
        -moz-box-shadow: 0 0px 5px rgba(0, 0, 0, 0.55), inset 1px 0px 3px rgba(255, 255, 255, 0.55);
        position: relative;
    }

    .phone12 .screenborder:before {
        content: '';
        position: absolute;
        right: -7px;
        top: 200px;
        width: 4px;
        height: 100px;
        background: #000;
        display: block;
        border-radius: 0 4px 4px 0;
    }

    .phone12 .screenborder .screen12 {
        width: 375px;
        display: block;
        height: 792px;
        padding: 8px;
        border-radius: 32px;
        background-color: #000;
        position: relative;
    }

    .phone12 .screenborder .screen12 iframe {
        display: none;
        width: 360px;
        border: 0;
        border-radius: 26px;
        height: 776px;
    }

    .phone12.blue,
    .phone12.blue .screenborder {
        background: #000;
        background: linear-gradient(40deg, #6BC048 0%, #4CAF50 50%, #2E7D32 100%);
    }

    /* ===== permission grid — แยก module ชัดเจน (admin add/edit + preset editor) ===== */
    .perm-module{border:1px solid #e3e6ea;border-radius:10px;margin:0 0 24px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08)}
    .perm-modhead{background:#3a3f51;padding:13px 18px;border-bottom:3px solid #2b2f3d}
    .perm-modhead .checkbox{margin:0;padding:0;display:flex;align-items:center}
    .perm-modhead .modtitle{font-weight:800;color:#fff !important;letter-spacing:.4px;text-shadow:0 1px 2px rgba(0,0,0,.3);padding-left:4px}
    .perm-modhead label,.perm-modhead .magic-checkbox+label,.perm-modhead .magic-checkbox:disabled+label,.perm-modhead .magic-checkbox[disabled]+label{color:#fff !important;opacity:1 !important;margin:0}
    .perm-modbody{padding:14px 10px 6px;background:#f7f8fa}
    .perm-headgrid{display:flex;flex-wrap:wrap;align-items:flex-start;margin:0 -5px}
    .perm-headcard{flex:0 0 33.333%;max-width:33.333%;padding:0 5px 10px;box-sizing:border-box}
    @media(max-width:991px){.perm-headcard{flex:0 0 50%;max-width:50%}}
    @media(max-width:600px){.perm-headcard{flex:0 0 100%;max-width:100%}}
    .perm-headcard .panel{margin:0;height:100%;border:1px solid #e3e6ea;border-radius:6px;box-shadow:none}
    .perm-headcard .panel-heading{background:#eef1f4;border-bottom:1px solid #e3e6ea;border-radius:6px 6px 0 0}
    .perm-caret{float:right;color:#9aa;margin-top:3px;transition:transform .15s}
    .panel-heading.collapsed .perm-caret{transform:rotate(-90deg)}
    .perm-count{color:#9aa0a6;font-weight:normal;margin-left:6px;vertical-align:middle}
    .perm-count-full{color:#197b30;font-weight:600}
    .perm-count-some{color:#d9822b}
</style>