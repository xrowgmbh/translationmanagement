<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0" 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	xmlns:image="http://ez.no/namespaces/ezpublish3/image/" 
	xmlns:tmp="http://ez.no/namespaces/ezpublish3/temporary/" 
	xmlns:ezobject="http://ez.no/object/" 
	xmlns:ezremote="http://ez.no/object" 
	xmlns:custom="http://ez.no/object/">

<!-- TEST 
	<xsl:template match="*">
	<dl><dt style="color: red;">Untranslated node: <strong><xsl:value-of select="name()"/></strong></dt>
	<dd>
	<xsl:copy>
      <xsl:apply-templates select="@*"/>
      <xsl:apply-templates select="node()"/>
    </xsl:copy>
	</dd>
	</dl>
	</xsl:template>
-->

	<!-- DEFAULT -->
	<xsl:template match="*">
		<xsl:copy>
			<xsl:apply-templates select="@*"/>
			<xsl:apply-templates select="node()"/>
		</xsl:copy>
	</xsl:template>



	<!-- Transform to HTML -->
	<xsl:template match="TranslationManagement">
		<html>
			<head/>
			<style type="text/css" media="all">
				table { border: 1px solid #000 }
			</style>

			<body style="margin: 0;padding: 0;text-align: center; background-color: #000;">
			<div style="background-color: #fff; margin: 1em auto; width: 80%; text-align: left; border: 1px solid #000; padding: 1em;">
				<xsl:apply-templates/>
			</div>
			</body>
		</html>
	</xsl:template>
	
	<xsl:template match="header[size='1']">
	<h6><xsl:apply-templates/></h6>
	</xsl:template>
    <xsl:template match="header[size='2']">
    <h6><xsl:apply-templates/></h6>
    </xsl:template>
    <xsl:template match="header[size='3']">
    <h3><xsl:apply-templates/></h3>
    </xsl:template>
    <xsl:template match="header[size='4']">
    <h4><xsl:apply-templates/></h4>
    </xsl:template>
    <xsl:template match="header[size='5']">
    <h5><xsl:apply-templates/></h5>
    </xsl:template>
    <xsl:template match="header[size='6']">
    <h6><xsl:apply-templates/></h6>
    </xsl:template>
	<xsl:template match="header">
    <h1><xsl:apply-templates/></h1>
    </xsl:template>
	<xsl:template match="paragraph">
		<p>
			<xsl:apply-templates/>
		</p>
	</xsl:template>
	<xsl:template match="line"><xsl:apply-templates/><br /></xsl:template>
	<xsl:template match="text">
		<p>
			<xsl:apply-templates/>
		</p>
	</xsl:template>
	<xsl:template match="link">
		<a href="{@href}"><xsl:apply-templates/></a>
	</xsl:template>
	<xsl:template match="ul">
		<ul><xsl:apply-templates/></ul>
	</xsl:template>
	<xsl:template match="li">
		<li><xsl:apply-templates/></li>
	</xsl:template>
	<xsl:template match="keyword-string">
	    <p><xsl:apply-templates/></p>
	</xsl:template>

	<!-- Divide each Object, but render children -->
	<xsl:template match="ezobject:version"><xsl:apply-templates/><hr /></xsl:template>
	
	
	<!-- HIDE but render children -->
	
	<xsl:template match="ezobject:*"><xsl:apply-templates/></xsl:template>
	<xsl:template match="section"><xsl:apply-templates/></xsl:template>

	<!-- HIDE completly -->
	<xsl:template match="noxl"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezauthor']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezbinaryfile']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezboolean']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezcountry']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezezdate']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezdatetime']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezemail']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezsrrating']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezfloat']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezinteger']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezisbn']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezmedia']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezobjectrealation']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezobjectrealationlist']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezprice']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='eztime']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezurl']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezuser']"></xsl:template>
	<xsl:template match="ezobject:attribute[@type='ezimage']">
		<p><xsl:value-of select="@alternative-text"/></p>
	</xsl:template>
    <xsl:template match="custom[@name='separator']">
		<hr />
    </xsl:template>

	<xsl:template match="custom">
    <table style="border-width:1px;border-style:dotted;border-color:red;">
    <caption>Customtag (<xsl:value-of select="@name"/>)</caption>
    <tr><td>
        <xsl:value-of select="@custom:title"/>
        <xsl:value-of select="@custom:text"/>
        <xsl:apply-templates/>
    </td></tr>
    </table>
    </xsl:template>
	<xsl:template match="embed">
	<div style="width: 100%;border-width:1px;border-style:dotted;border-color:green;">
	   <xsl:if test="@align='left'"> 
	   </xsl:if>
	   <xsl:if test="@align='right'"> 
	   </xsl:if>
	          Object <xsl:value-of select="@object_remote_id"/> (<xsl:value-of select="@size"/><xsl:if test="@align">, </xsl:if><xsl:value-of select="@align"/>)
       </div>
	</xsl:template>
	<xsl:template match="embed-inline">
	<div style="width: 100%;border-width:1px;border-style:dotted;border-color:green;">
	   <xsl:if test="@align='left'"> 
	   </xsl:if>
	   <xsl:if test="@align='right'"> 
	   </xsl:if>
	          Object <xsl:value-of select="@object_remote_id"/> (<xsl:value-of select="@size"/><xsl:if test="@align">, </xsl:if><xsl:value-of select="@align"/>)
       </div>
	</xsl:template>
</xsl:stylesheet>
