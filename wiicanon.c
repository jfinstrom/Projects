/*
 *      wiicanon.c
 *      
 *      Copyright 2009 James Finstrom <jfinstrom@gmail.com>
 *      
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 */


/*  version 0.3
 *  purpose remote trigger canon camera via wiimote
 *  Known Issues: wiimote segfaults 1st attempt
 *                libgphoto2 when used in this generic mannor may connect
 *                to a device that in NOT the canon like a media card 
 * 
 *  COMPILE:      cc wiicanon.c -lcwiid -lgphoto2 -o wiicanon
 * 
 * version 0.2 CHANGES
 * Add options to allow specification of filename and dir on command line
 * Reduce chatter by adding a -v option to allow chatter only is set
 * 
 * version 0.3 CHANGES
 * Ground up rewrite scrap the options dont need em cause I wrote it right
 * this time around. Cleaned up the code added a multi capture feature.
 * killed a most of the frankenstein coding...
 *   
 */

#include <cwiid.h>
#include <fcntl.h>
#include <gphoto2/gphoto2-abilities-list.h>
#include <gphoto2/gphoto2-camera.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>




static GPPortInfoList *portinfolist = NULL;
static CameraAbilitiesList *abilities = NULL;

do_autodetect (CameraList * list, GPContext * context)
{
  int ret, i;
  CameraList *xlist = NULL;

  ret = gp_list_new (&xlist);
  if (ret < GP_OK)
    goto out;
  if (!portinfolist)
    {
      /* Load all the port drivers we have... */
      ret = gp_port_info_list_new (&portinfolist);
      if (ret < GP_OK)
	goto out;
      ret = gp_port_info_list_load (portinfolist);
      if (ret < 0)
	goto out;
      ret = gp_port_info_list_count (portinfolist);
      if (ret < 0)
	goto out;
    }
  /* Load all the camera drivers we have... */
  ret = gp_abilities_list_new (&abilities);
  if (ret < GP_OK)
    goto out;
  ret = gp_abilities_list_load (abilities, context);
  if (ret < GP_OK)
    goto out;

  /* ... and autodetect the currently attached cameras. */
  ret = gp_abilities_list_detect (abilities, portinfolist, xlist, context);
  if (ret < GP_OK)
    goto out;

  /* Filter out the "usb:" entry */
  ret = gp_list_count (xlist);
  if (ret < GP_OK)
    goto out;
  for (i = 0; i < ret; i++)
    {
      const char *name, *value;

      gp_list_get_name (xlist, i, &name);
      gp_list_get_value (xlist, i, &value);
      if (!strcmp ("usb:", value))
	continue;
      gp_list_append (list, name, value);
    }
out:
  gp_list_free (xlist);
  return gp_list_count (list);
}

do_open_camera (Camera ** camera, const char *model, const char *port)
{
  int ret, m, p;
  CameraAbilities a;
  GPPortInfo pi;

  ret = gp_camera_new (camera);
  if (ret < GP_OK)
    return ret;

  /* First lookup the model / driver */
  m = gp_abilities_list_lookup_model (abilities, model);
  if (m < GP_OK)
    return ret;
  ret = gp_abilities_list_get_abilities (abilities, m, &a);
  if (ret < GP_OK)
    return ret;
  ret = gp_camera_set_abilities (*camera, a);
  if (ret < GP_OK)
    return ret;

  /* Then associate the camera with the specified port */
  p = gp_port_info_list_lookup_path (portinfolist, port);
  if (ret < GP_OK)
    return ret;
  switch (p)
    {
    case GP_ERROR_UNKNOWN_PORT:
      fprintf (stderr, "The port you specified "
	       "('%s') can not be found. Please "
	       "specify one of the ports found by "
	       "'gphoto2 --list-ports' and make "
	       "sure the spelling is correct "
	       "(i.e. with prefix 'serial:' or 'usb:').", port);
      break;
    default:
      break;
    }
  if (ret < GP_OK)
    return ret;
  ret = gp_port_info_list_get_info (portinfolist, p, &pi);
  if (ret < GP_OK)
    return ret;
  ret = gp_camera_set_port_info (*camera, pi);
  if (ret < GP_OK)
    return ret;
  return GP_OK;
}

do_multi_capture (Camera * cam, GPContext * context)
{
  CameraFilePath path;
  gp_camera_capture (cam, GP_CAPTURE_IMAGE, &path, context);
  sleep (1);
  gp_camera_capture (cam, GP_CAPTURE_IMAGE, &path, context);
  sleep (1);
  gp_camera_capture (cam, GP_CAPTURE_IMAGE, &path, context);
}

do_capture (Camera * cam, GPContext * context)
{
  CameraFilePath path;
  CameraFile *file = NULL;
  unsigned long int filesize;
  const char *filedata;

  if (gp_camera_capture (cam, GP_CAPTURE_IMAGE, &path, context) == 0)
    {
      gp_file_new (&file);
      printf ("Saving File to %s\n", path.name);
      gp_camera_file_get (cam, path.folder, path.name, GP_FILE_TYPE_NORMAL,
			  file, NULL);
      gp_camera_file_delete (cam, path.folder, path.name, NULL);
      gp_file_get_data_and_size (file, &filedata, &filesize);
      int fd = open (path.name, O_CREAT | O_WRONLY, 0644);
      write (fd, filedata, filesize);
      close (fd);
    }
  return GP_OK;

}

static cwiid_wiimote_t *g_wiimote = NULL;
static struct cwiid_state state;

void
do_conn_wiimote ()
{
  bdaddr_t g_bdaddr = *BDADDR_ANY;
  printf ("Press buttons 1 and 2 on the Wiimote to connect... \n\n");
  fflush (stdout);
  g_wiimote =
    cwiid_connect (&g_bdaddr, CWIID_FLAG_CONTINUOUS | CWIID_FLAG_NONBLOCK);
  cwiid_command (g_wiimote, CWIID_CMD_RPT_MODE, CWIID_RPT_BTN);
  cwiid_command (g_wiimote, CWIID_CMD_LED, 9);
  void print_state (struct cwiid_state *state);
}


int
main (int argc, char **argv)
{

  CameraList *list;
  Camera **cams, *cam;
  int ret, i, m, p, count;
  const char *name, *value;
  GPContext *context;
  CameraAbilities a;
  GPPortInfo pi;
  bdaddr_t g_bdaddr = *BDADDR_ANY;
  int laststate;
  context = gp_context_new ();
  ret = gp_list_new (&list);
  if (ret < GP_OK)
    return 1;
  count = do_autodetect (list, context);
  cams = calloc (sizeof (Camera *), count);
  for (i = 0; i < count; i++)
    {
      gp_list_get_name (list, i, &name);
      gp_list_get_value (list, i, &value);
      if (!strcspn (value, "usb:"))
	{
	  printf ("Port found: %s\n", value);
	  i = count;

	}
    }

  if (do_open_camera (&cam, name, value) == 0)
    {
      printf ("Camera Connected\n");
    }
  else
    {
      printf ("Could not attach to the camera");
    }

  do_conn_wiimote ();


  while (1)
    {

      cwiid_get_state (g_wiimote, &state);

      switch (state.buttons)
	{
	case 4:
	  if (laststate == 0)
	    {
	      laststate = 4;
	      printf ("Capturing...\n");
	      if (do_capture (cam, context) == 0)
		{
		  printf ("File Saved \n");
		}
	    }
	  break;
	case 8:
	  if (laststate == 0)
	    {
	      laststate = 8;
	      printf ("Capturing in 3\n");
	      cwiid_command (g_wiimote, CWIID_CMD_LED, 14);
	      sleep (1);
	      printf ("Capturing in 2\n");
	      cwiid_command (g_wiimote, CWIID_CMD_LED, 12);
	      sleep (1);
	      printf ("Capturing in 1\n");
	      cwiid_command (g_wiimote, CWIID_CMD_LED, 8);
	      sleep (1);
	      printf ("Capturing...\n");
	      cwiid_command (g_wiimote, CWIID_CMD_LED, 9);
	      if (do_capture (cam, context) == 0)
		{
		  printf ("File Saved \n");
		}
	    }
	  break;
	case 2048:
	  if (laststate == 0)
	    {
	      laststate = 2048;
	      printf ("UP");
	    }
	  break;
	case 1024:
	  if (laststate == 0)
	    {
	      laststate = 1024;
	      do_multi_capture (cam, context);
	    }
	  break;
	case 512:
	  if (laststate == 0)
	    {
	      laststate = 512;
	      printf ("RIGHT");
	    }
	  break;
	case 256:
	  if (laststate == 0)
	    {
	      laststate = 256;
	      printf ("LEFT");
	    }
	  break;
	case 16:
	  if (laststate == 0)
	    {
	      laststate = 16;
	      printf ("-");
	    }
	  break;
	case 4096:
	  if (laststate == 0)
	    {
	      laststate = 4096;
	      printf ("+");
	    }
	  break;
	case 128:
	  if (laststate == 0)
	    {
	      laststate = 128;
	      printf ("Exiting\n");
	      /* Clean up everybody do your share.. */
	      gp_camera_exit (cam, context);
	      cwiid_disconnect (g_wiimote);
	      fflush (stdout);
	      exit (0);
	    }
	  break;
	case 1:
	  if (laststate == 0)
	    {
	      laststate = 1;
	      printf ("2");
	    }
	  break;
	case 2:
	  if (laststate == 0)
	    {
	      laststate = 2;
	      printf ("1");
	    }
	  break;
	case 0:
	  laststate = 0;
	  break;
	}
      fflush (stdout);
    }

  return 0;

}
