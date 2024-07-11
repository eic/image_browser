# image_browser
Web application to display data quality plots: https://eic.jlab.org/epic/image_browser.html

## Front end

Designs and prototypes are developed in Figma. If you have a Figma account, the design files can be shared with you. If you don't have a Figma account, you don't need one to contribute. 
Currently, the page is built using HTML, functionality is given via Javascript, and CSS is used to make it *pretty*.  There is a version written in React on my local machine that has been fun to develop.

To fetch information from the database for display on the page, we use PHP (though not required). 

## Back end  
The system to display images is really quite simple. There is a directory, `/work/eic3/validation/images/` at Jefferson Lab that stores the raw image files. When new images are added here, a python script (image_crawler) checks to see if the database has seen the image before. If yes, the script moves on; else it inserts into the Plots table in the database.

The database contains tables. The most important ones for this application are Plots and Plot_Types. Plot_Types stores information about the image including: name, filetype, point of contact, and any relevant "groups" the image belongs to. The Plots table stores the reference to the image itself, including: the path to the image, its corresponding ID from the Plot_Types table, and any relevant metadata (like a repo, PR, commit etc) associated with it. An example is shown below:


Plot_Types:
| ID | Name                    | FileType | Description | PointOfContact | PlotGroup |
|----|-------------------------|----------|-------------|----------------|-----------|
| 42 | genJetConstituentEnergy | png      | NULL        | jet_person@place.org  | 31        |

Plots: 
| ID   | Plot_Types_ID | Root Path                                                                        | MetaData |
|------|---------------|-----------------------------------------------------------------------------|----------|
| 2389 | 42            | /work/eic3/validation/images/24.03.1/epic_craterlake/DIS/NC/18x275/minQ2=1/ | NULL     |

Now, if any future images named `genJetConstituentEnergy` show up in any subdirectory that is being watched by the crawler, they will automatically appear on the page.

## FAQs  

### How do I add plots to the page?
There are multiple ways to do this. You can (and should) submit a macro to the benchmarks repository. You can also send it to the validation working group via email, Mattermost, etc and we will work with you to get it incorporated. 

### Can we ignore a plot once it is added?
Yes. The database will still track the images but they will not be visible to the end user.

### Can the image crawler "crawl" over multiple directories?
Yes. The only requirement is that it is a subdirectory of `/work/eic3/validation/images` as not *all* directories are visible to the web server. 

### How often does the page update with new plots?
This is configurable. The crawler itself checks for duplicates in the database and if there is a crawler process already running, so we can update multiple times daily if need be. 






