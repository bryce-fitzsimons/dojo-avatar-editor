# dojo-avatar-editor
By: Bryce Fitzsimons<br>
bryce1&lt;at&gt;gmail&lt;.&gt;com

This large PHP class handles everything Avatar-related for Dojo Games (_dojo.com_). It relies on ImageMagick, a robust image editing library. This class will do all of the following, depending on how it is invoked or which methods are called. To see it in action, visit http://www.dojo.com and click "Login" or "Register" to begin the avatar creation process.

1. Render a user's avatar as a small, medium, or large PNG image. Pull from a saved image, or if none exists yet, then render one based on the user's saved avatar customization.
2. Render a user's avatar is a series of overlaid CSS sprite layers, each sprite representing a different layer (body type, clothes, facial features, accessories, etc).
3. Render a full HTML/CSS/Jquery/AJAX avatar customization widget with UI.
4. Generate a random avatar, for when new users register.
