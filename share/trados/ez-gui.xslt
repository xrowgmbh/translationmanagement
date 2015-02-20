<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	xmlns:ezobject="a" xmlns:ezremote="b" xmlns:custom="c">
	<xsl:template match="/">
		<html>
			<head/>
			<body style="font-family: sans-serif;">
				<xsl:apply-templates/>
			</body>
		</html>
	</xsl:template>
	
	<xsl:template match="context">
		<table style="width:100%; background-color: #EEE;empty-cells:hide;">
		<tr style="background-color: #FFF;">
		<th style="text-align: left;">Source (<xsl:value-of select="name" />)</th>
		<th style="text-align: left;">Translation (<xsl:value-of select="name" />)</th>
		<th style="width: 1%;">Comment</th>
		</tr>
				<xsl:apply-templates/>
		</table>
	</xsl:template>
	
	<xsl:template match="message">
		<tr>
        <xsl:apply-templates/>
		<xsl:variable name="wert" select="comment" />
		<xsl:choose>
		<xsl:when test="$wert">
		<td style="color:black; padding:0.2em;border: 1px dotted #CCC; ">
			<xsl:value-of select="comment" />
		</td>
		</xsl:when>
		</xsl:choose>
		</tr>
	</xsl:template>
	
	<xsl:template match="name"></xsl:template>
	
	<xsl:template match="source">
		<td style="color:black; padding:0.2em;border: 1px dotted #CCC;">
			<xsl:apply-templates/>
		</td>
	</xsl:template>
	
	<xsl:template match="comment"></xsl:template>
	
	<xsl:template match="translated">
		<td style="color:black; padding:0.2em;border: 1px dotted #CCC;">
			<xsl:apply-templates/>
		</td>
	</xsl:template>
	
	<xsl:template match="noxl">
		<span style="color: red; font-style:italic;">
		<xsl:value-of select="@start"/>
		<xsl:value-of select="@name"/>
		<xsl:value-of select="@end"/>
		</span>
	</xsl:template>
</xsl:stylesheet>
