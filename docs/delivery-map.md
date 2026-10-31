# Google delivery maps

Checkout and saved customer addresses share the Google map and pin controls.
The pin is saved as a Google Maps coordinate link in the existing additional
address details field. Existing notes are retained; no database changes are needed.

Set these in local `.env` and in Render Environment:

```dotenv
GOOGLE_MAPS_API_KEY=your_browser_maps_key
GOOGLE_MAPS_MAP_ID=your_javascript_map_id
```

Enable **Maps JavaScript API** and **Geocoding API** in the matching Google Cloud
project and enable billing. Restrict the browser key to those APIs and your
website HTTP referrers, including `http://localhost:8000/*` for local testing and
your exact Render HTTPS domain. Browser map keys are visible by design; never put
a private server credential in this setting. Use a JavaScript map ID for
production; the example `DEMO_MAP_ID` is used if a map ID is not configured.

Choose Find address, click the map or drag the marker, then choose Use this pin.
Use current location requests browser permission. Editing the written location
clears an old pin, while loading a saved address restores its saved pin.
Geocoding positions are suggestions until the customer confirms them. Map failure
does not block entering or ordering with a written address. Localhost and HTTPS
support browser geolocation; other HTTP hosts may not.

The existing 255-character notes limit remains enforced. Reduce long delivery
notes if there is not enough room to add the pin. Pin coordinates are delivery
information, not proof of a verified address or residence.

References:
- [Google Maps setup](https://developers.google.com/maps/documentation/javascript/get-api-key)
- [Draggable markers](https://developers.google.com/maps/documentation/javascript/advanced-markers/draggable-markers)
