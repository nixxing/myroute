package com.example.myroute;

import java.io.BufferedReader;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.util.ArrayList;
import java.util.List;
import java.util.Timer;
import java.util.TimerTask;

import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.client.HttpClient;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.impl.client.DefaultHttpClient;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import com.google.android.gms.maps.CameraUpdateFactory;
import com.google.android.gms.maps.GoogleMap;
import com.google.android.gms.maps.MapFragment;
import com.google.android.gms.maps.model.BitmapDescriptorFactory;
import com.google.android.gms.maps.model.LatLng;
import com.google.android.gms.maps.model.Marker;
import com.google.android.gms.maps.model.MarkerOptions;

import android.location.LocationManager;
import android.os.Bundle;
import android.os.Handler;
import android.os.StrictMode;
import android.app.Activity;
import android.content.Context;
import android.graphics.Color;
import android.text.method.ScrollingMovementMethod;
import android.util.Log;
import android.view.Menu;
import android.view.View;
import android.view.Window;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.Spinner;
import android.widget.TextView;

public class MainActivity extends Activity {
	
	private GoogleMap map;
	private Marker userMarker;
	public LocationManager locMan;
	GPSTracker gps;
	double latitude;
	double longitude;
	TextView description;
	private Spinner routes;
	private Button btnSubmit;
	String[] allRoutes;
	private boolean firstLoad = false;
	private int currentID = 0;
	private String baseURL = "http://192.168.0.207/TrackMyRoute/api/router/";
	
	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		setContentView(R.layout.activity_main);
		StrictMode.enableDefaults();
		
		// Let's get everything we need from our layout
		description = (TextView) findViewById(R.id.description);
		btnSubmit = (Button) findViewById(R.id.btnSubmit);
		
		// Make sure we are able to scroll on the description field
		description.setMovementMethod(ScrollingMovementMethod.getInstance());
		
		getLocation();
		String data = getData("routes");
		fillSpinner(data);
	}

	@Override
	public boolean onCreateOptionsMenu(Menu menu) {
		// Inflate the menu; this adds items to the action bar if it is present.
		getMenuInflater().inflate(R.menu.main, menu);
		return true;
	}
	
	
	// Set up the map and use the GPSTracker class to get the position of the user
	public void getLocation(){
		if(map==null){
			//get the map
	    	map = ((MapFragment)getFragmentManager().findFragmentById(R.id.map)).getMap();
			//check in case map/ Google Play services not available
			if(map!=null){ 
				// We're using a timer to update the map every 5 s
			    final Handler handler = new Handler(); 
			    Timer timer2 = new Timer(); 
			    TimerTask timertask = new TimerTask() {
			        public void run() { 
			            handler.post(new Runnable() {
			                public void run() {
			                	
			                	// Remove the user everytime it updates
			                	if(userMarker != null) {
			        	    		userMarker.remove();
			        	    	}
			                	
			                	locMan = (LocationManager)getSystemService(Context.LOCATION_SERVICE);
								//*************get last location*************//
								gps = new GPSTracker(MainActivity.this);
					            // Check if GPS enabled    
								if(gps.canGetLocation()){
					               
									latitude = gps.getLatitude();
									longitude = gps.getLongitude();
								}else{
									// Ask user to enable GPS/network in settings
									gps.showSettingsAlert();
								}
					            //******************************************//
								LatLng lastLatLng = new LatLng(latitude, longitude);
								
								// Show the user
								userMarker = map.addMarker(new MarkerOptions()
							        .position(lastLatLng)
							        .title("You're here now!")
							        .snippet("Follow the route ;)")
							        .icon(BitmapDescriptorFactory
							        .fromResource(R.drawable.popeye)));
								
								// Check whether user close to a checkpoint or not
								checkPoint(latitude, longitude);

								// Move the camera instantly to the user (only the first time)
								if(firstLoad == false) {
									map.moveCamera(CameraUpdateFactory.newLatLngZoom(lastLatLng, 15));
									firstLoad = true;
								}
			                }
			            });
			        }
			    };
			    timer2.schedule(timertask, 3000, 5000); // First run at 3s, update every 5s!
			}
	    }
	}
	
	// Check whether you are close to a checkpoint or not
	public void checkPoint(double lati, double longi) {
		String data = getData("route_info/" + currentID);
		
		try {  
    	   JSONArray arr = new JSONArray(data);
    	   String title = "";
    	   int n = arr.length();
           for (int i = 0; i < n; i++) {
               JSONObject jo = arr.getJSONObject(i);

               // Fill array
               latitude = jo.getDouble("latitude");
               longitude = jo.getDouble("longitude");
               title = jo.getString("checkpoint");
               if((lati * 100000) > (latitude * 100000 - 60) && (lati * 100000) < (latitude * 100000 + 60)) {
            	   if((longi * 1000000) > (longitude * 1000000 - 1000) && (longi * 1000000) < (longitude * 1000000 + 1000)) {
                	   // Show the checkpoint its description!
            		   description.setText(jo.getString("description"));
                   }
            	   else {
            		   description.setText("Walk to a checkpoint if you want to learn something new.");
            	   }
               }
               else {
        		   description.setText("Walk to a checkpoint if you want to learn something new.");
        	   }
           }
	    	   
 	  } catch (JSONException e) {
 	   // TODO Auto-generated catch block
 	   e.printStackTrace();
 	  }
		
	}
	
	// Get a json string from the api
	public String getData(String url){
    	String result = "";
    	String dataUrl = baseURL + url;
    	InputStream isr = null;
    	try {
	        HttpClient client = new DefaultHttpClient();  
	        HttpGet get = new HttpGet(dataUrl);
	        HttpResponse responseGet = client.execute(get);  
	        HttpEntity resEntityGet = responseGet.getEntity();  
	        if (resEntityGet != null) {  
	           //do something with the response
	           isr = resEntityGet.getContent();
	        }
		} catch (Exception e) {
		    e.printStackTrace();
		}
	    // Convert response to string
	    try{
            BufferedReader reader = new BufferedReader(new InputStreamReader(isr,"UTF-8"),8);
            StringBuilder sb = new StringBuilder();
            String line = null;
            while ((line = reader.readLine()) != null) {
                    sb.append(line + "\n");
            }
            isr.close();
     
            result=sb.toString();
	    }
	    catch(Exception e){
            Log.e("log_tag", "Error  converting result "+e.toString());
	    }
	    return result;
	}
    
	// Use the json string to set all checkpoints of a route on the map
	public void setCheckpoints(String result) {
		
		map.clear(); // Get rid of all previous marker if a new route is selected
		
	    // Parse json data
	    try {
	    	   
	    	   JSONArray arr = new JSONArray(result);
	    	   String title = "";
	    	   int n = arr.length();
	           for (int i = 0; i < n; i++) {
	               JSONObject jo = arr.getJSONObject(i);

	               //Fill array
	               latitude = jo.getDouble("latitude");
	               longitude = jo.getDouble("longitude");
	               title = jo.getString("checkpoint");
	               
	               setMarker(latitude, longitude, title);
	           }
	    	   
    	} catch (JSONException e) {
    		// TODO Auto-generated catch block
    		e.printStackTrace();
    	}
	}
	
	// Placing the spinach on the map
	public void setMarker(double lati, double longi, String title) {
		LatLng lastLatLng = new LatLng(lati, longi);
		map.addMarker(new MarkerOptions()
	        .position(lastLatLng)
	        .title(title)
	        .icon(BitmapDescriptorFactory
	        .fromResource(R.drawable.spinach)));
	}
	
	// Filling the spinner with the routes we got from the api
	public void fillSpinner(String result) {
	    //parse json data
	    try {
    	   JSONArray arr = new JSONArray(result);
    	   JSONObject jObj = arr.getJSONObject(0);
    	   routes = (Spinner) findViewById(R.id.spinner2);
    	   // We put them in a list so we can easily add it to the spinner
    	   List<String> list = new ArrayList<String>();
    	   String data = "";
    	   int n = arr.length();
    	   allRoutes = new String[(n + 1)]; //Declare array
           for (int i = 0; i < n; i++) {
               JSONObject jo = arr.getJSONObject(i);
                
               data = data + " " + jo.getString("route_name");
               
               //Fill array
               allRoutes[(int)jo.getInt("id")] = (String)jo.getString("route_name");
                
               list.add(jo.getString("route_name")/* + " (" + jo.getString("city") + ")"*/);
           }
           ArrayAdapter<String> dataAdapter = new ArrayAdapter<String>(this,
           R.drawable.spinner_text, list);
           dataAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
           routes.setAdapter(dataAdapter);
    	   
	    } catch (JSONException e) {
	    	// TODO Auto-generated catch block
	    	e.printStackTrace();
	    }
	}
		
	// Get everything set up once a route is selected
	public void selectRoute(View view) {
		 
		routes = (Spinner) findViewById(R.id.spinner2);
		description.setText(routes.getSelectedItem().toString());
		
		currentID = 0;
		String test = routes.getSelectedItem().toString();
		//String test1 = test.split("\\s")[0];
		for (int r=1; r<allRoutes.length; r++) {
			if(allRoutes[r] == test) {
				currentID = r;
			}
		}
		
		String result = getData("route_info/" + Integer.toString(currentID));
		setCheckpoints(result);
	}
}
