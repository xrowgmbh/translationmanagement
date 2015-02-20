<script language="JavaScript1.2" type="text/javascript">
menuArray['ContextMenu']['elements']['menu-tm-translate']= new Array();
menuArray['ContextMenu']['elements']['menu-tm-translate']['url'] = {"/tm/manager/%objectID%"|ezurl};
menuArray['ContextMenu']['elements']['menu-version-history']= new Array();
menuArray['ContextMenu']['elements']['menu-version-history']['url'] = {"/content/history/%objectID%"|ezurl};
</script>
<hr/>
<a id="menu-tm-translate" href="#" onmouseover="ezpopmenu_mouseOver( 'ContextMenu' )">{"Translate"|i18n("design/admin/popupmenu")}</a>
<a id="menu-version-history" href="#" onmouseover="ezpopmenu_mouseOver( 'ContextMenu' )">{"Version History"|i18n("design/admin/popupmenu")}</a>
{* Translate document *}
<form id="menu-form-tm-translate" method="post" action={"/tm/manager/"|ezurl}>
  <input type="hidden" name="NodeID" value="%nodeID%" />
  <input type="hidden" name="ObjectID" value="%objectID%" />
  <input type="hidden" name="CurrentURL" value="%currentURL%" />
</form>