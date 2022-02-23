const apiKey = 'sandbox' ;  // limited calls. Contact info@culturecreates.com for your apiKey.
const format = 'json' ;  // json or jsonld
const source = 'http://kg.artsdata.ca/culture-creates/huginn/capacoa-members' ; // see list at https://s.zazuko.com/7w6bJ
const baseUrl = 'http://localhost:3003' ;
const limit = 100 ;
const endPointIndex = `${baseUrl}/organizations`;
const endPointRanked = `${baseUrl}/ranked` ;
const endPointEvents = `${baseUrl}/events` ;
const artsdataApiIndex = `${endPointIndex}?apiKey=${apiKey}&format=${format}&source=${source}&limit=${limit}` ;
const artsdataApiDetail = `${endPointRanked}?apiKey=${apiKey}&format=${format}&frame=ranked_org` ;
const artsdataApiEvents = `${endPointEvents}?apiKey=${apiKey}&format=${format}&frame=event_location` ;
export { artsdataApiIndex, artsdataApiDetail, artsdataApiEvents} ;