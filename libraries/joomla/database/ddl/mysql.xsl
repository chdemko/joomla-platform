<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="text" />
	<xsl:template match="/">
		<xsl:for-each select="database/table">
			<xsl:text>CREATE TABLE </xsl:text>
			<xsl:value-of select="@name" />
			<xsl:text> (&#10;</xsl:text>
			<xsl:for-each select="./column">
				<xsl:text>&#9;`</xsl:text><xsl:value-of select="@name" /><xsl:text>` </xsl:text>
				<xsl:choose>
					<xsl:when test="@type='VARCHAR'">
					</xsl:when>
					<xsl:when test="@type='INTEGER'">
						<xsl:text>INT</xsl:text>
						<xsl:if test="current()[@size]">
							<xsl:text>(</xsl:text>
							<xsl:value-of select="@size" />
							<xsl:text>)</xsl:text>
						</xsl:if>
					</xsl:when>
				</xsl:choose>
				<xsl:if test="current()[@required] and @required='true'">
					<xsl:text> NOT NULL</xsl:text>
				</xsl:if>
				<xsl:if test="current()[@default]">
					<xsl:text> DEFAULT '</xsl:text>
					<xsl:value-of select="@default" />
					<xsl:text>'</xsl:text>
				</xsl:if>
				<xsl:text>,&#10;</xsl:text>
			</xsl:for-each>
			<xsl:text>) DEFAULT CHARSET=utf8;&#10;</xsl:text>
		</xsl:for-each>
	</xsl:template>
</xsl:stylesheet>

